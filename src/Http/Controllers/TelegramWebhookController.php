<?php

namespace Enessvg\LaravelTelegramDeployer\Http\Controllers;

use Enessvg\LaravelTelegramDeployer\DTO\TelegramMessageContext;
use Enessvg\LaravelTelegramDeployer\Jobs\ExecuteTelegramAction;
use Enessvg\LaravelTelegramDeployer\Models\TelegramDeployerRun;
use Enessvg\LaravelTelegramDeployer\Services\GlobalRunLock;
use Enessvg\LaravelTelegramDeployer\Services\ReplayGuard;
use Enessvg\LaravelTelegramDeployer\Services\RunCommandParser;
use Enessvg\LaravelTelegramDeployer\Services\TelegramApiClient;
use Enessvg\LaravelTelegramDeployer\Services\TelegramAuthorizer;
use Enessvg\LaravelTelegramDeployer\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        RunCommandParser $parser,
        TelegramAuthorizer $authorizer,
        TotpService $totp,
        ReplayGuard $replayGuard,
        GlobalRunLock $globalRunLock,
        TelegramApiClient $telegramApiClient,
    ): JsonResponse {
        $expectedSecret = (string) config('telegram-deployer.telegram.webhook_secret', '');

        if ($expectedSecret !== '') {
            $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

            if (! hash_equals($expectedSecret, $providedSecret)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'forbidden',
                ], 403);
            }
        }

        $context = TelegramMessageContext::fromUpdate((array) $request->all());

        if ($context === null) {
            return response()->json([
                'ok' => true,
                'message' => 'ignored',
            ]);
        }

        $parsed = $parser->parse($context->text);

        if ($parsed === null) {
            return response()->json([
                'ok' => true,
                'message' => 'ignored',
            ]);
        }

        if (! $authorizer->isAllowed($context->chatId, $context->userId)) {
            $this->notify($telegramApiClient, $context->chatId, 'Unauthorized request.');

            return response()->json([
                'ok' => false,
                'message' => 'unauthorized',
            ]);
        }

        if (! array_key_exists($parsed->action, (array) config('telegram-deployer.actions', []))) {
            $this->notify($telegramApiClient, $context->chatId, "Unknown action: {$parsed->action}");

            return response()->json([
                'ok' => false,
                'message' => 'unknown_action',
            ]);
        }

        try {
            $counter = $totp->verify($parsed->token);
        } catch (RuntimeException $exception) {
            report($exception);
            $this->notify($telegramApiClient, $context->chatId, 'Server TOTP is not configured.');

            return response()->json([
                'ok' => false,
                'message' => 'server_misconfigured',
            ]);
        }

        if ($counter === null) {
            $this->notify($telegramApiClient, $context->chatId, 'Invalid token.');

            return response()->json([
                'ok' => false,
                'message' => 'invalid_token',
            ]);
        }

        if (! $replayGuard->consume($parsed->token, $counter, $parsed->action)) {
            $this->notify($telegramApiClient, $context->chatId, 'Token already used in this time window.');

            return response()->json([
                'ok' => false,
                'message' => 'token_replayed',
            ]);
        }

        if (! $globalRunLock->probeAvailable()) {
            $this->notify($telegramApiClient, $context->chatId, 'Another run is currently in progress.');

            return response()->json([
                'ok' => false,
                'message' => 'busy',
            ]);
        }

        $run = TelegramDeployerRun::create([
            'action' => $parsed->action,
            'status' => 'pending',
            'chat_id' => $context->chatId,
            'user_id' => $context->userId,
            'username' => $context->username,
            'request_text' => $context->text,
            'summary' => 'Run accepted and queued.',
        ]);

        $job = new ExecuteTelegramAction($run->id);
        $connection = config('telegram-deployer.queue.connection');
        $queue = (string) config('telegram-deployer.queue.name', 'default');

        if (is_string($connection) && $connection !== '') {
            $job->onConnection($connection);
        }

        if ($queue !== '') {
            $job->onQueue($queue);
        }

        dispatch($job);

        $this->notify(
            $telegramApiClient,
            $context->chatId,
            "Accepted: {$parsed->action} (run #{$run->id})",
        );

        return response()->json([
            'ok' => true,
            'message' => 'accepted',
            'run_id' => $run->id,
        ]);
    }

    private function notify(TelegramApiClient $telegramApiClient, string $chatId, string $text): void
    {
        try {
            $telegramApiClient->sendMessage($chatId, $text);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
