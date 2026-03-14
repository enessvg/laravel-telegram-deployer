<?php

namespace Enessvg\LaravelTelegramDeployer\Jobs;

use Enessvg\LaravelTelegramDeployer\Models\TelegramDeployerRun;
use Enessvg\LaravelTelegramDeployer\Services\ActionFailedException;
use Enessvg\LaravelTelegramDeployer\Services\ActionRunner;
use Enessvg\LaravelTelegramDeployer\Services\GlobalRunLock;
use Enessvg\LaravelTelegramDeployer\Services\TelegramApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteTelegramAction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $runId,
    ) {
    }

    public function handle(
        ActionRunner $runner,
        GlobalRunLock $globalRunLock,
        TelegramApiClient $telegramApiClient,
    ): void {
        $run = TelegramDeployerRun::query()->find($this->runId);

        if ($run === null) {
            return;
        }

        $lock = $globalRunLock->acquire();

        if ($lock === null) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => 'Run rejected: another run already in progress.',
                'error_message' => 'global_lock_unavailable',
            ]);

            $this->notify($telegramApiClient, $run->chat_id, "Run #{$run->id} failed: another run is in progress.");

            return;
        }

        $run->update([
            'status' => 'running',
            'started_at' => now(),
            'summary' => 'Run started.',
        ]);

        try {
            $steps = $runner->run($run->action);

            $run->update([
                'status' => 'succeeded',
                'finished_at' => now(),
                'steps' => $steps,
                'summary' => sprintf('Run succeeded: %d step(s) executed.', count($steps)),
                'error_message' => null,
            ]);

            $this->notify($telegramApiClient, $run->chat_id, "Run #{$run->id} succeeded.");
        } catch (ActionFailedException $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'steps' => $exception->steps,
                'summary' => 'Run failed.',
                'error_message' => $exception->getMessage(),
            ]);

            $this->notify($telegramApiClient, $run->chat_id, "Run #{$run->id} failed: {$exception->getMessage()}");

            throw $exception;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => 'Run failed.',
                'error_message' => $exception->getMessage(),
            ]);

            $this->notify($telegramApiClient, $run->chat_id, "Run #{$run->id} failed: {$exception->getMessage()}");

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    private function notify(TelegramApiClient $telegramApiClient, ?string $chatId, string $text): void
    {
        if ($chatId === null) {
            return;
        }

        try {
            $telegramApiClient->sendMessage($chatId, $text);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
