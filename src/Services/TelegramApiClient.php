<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramApiClient
{
    public function sendMessage(string $chatId, string $text): array
    {
        return $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => false,
        ];

        if ($secretToken !== null && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        return $this->request('setWebhook', $payload);
    }

    private function request(string $method, array $payload): array
    {
        $token = (string) config('telegram-deployer.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('TELEGRAM_DEPLOYER_BOT_TOKEN is not configured.');
        }

        $response = Http::timeout(20)
            ->baseUrl("https://api.telegram.org/bot{$token}")
            ->post($method, $payload);

        if (! $response->successful()) {
            throw new RuntimeException("Telegram API request failed: {$response->status()} {$response->body()}");
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new RuntimeException('Telegram API response is not valid JSON.');
        }

        return $decoded;
    }
}
