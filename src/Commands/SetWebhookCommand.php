<?php

namespace Enessvg\LaravelTelegramDeployer\Commands;

use Enessvg\LaravelTelegramDeployer\Services\TelegramApiClient;
use Illuminate\Console\Command;
use RuntimeException;

class SetWebhookCommand extends Command
{
    protected $signature = 'telegram-deployer:set-webhook {url? : Full webhook URL}';

    protected $description = 'Register Telegram webhook for deploy commands.';

    public function handle(TelegramApiClient $telegramApiClient): int
    {
        $url = (string) ($this->argument('url') ?: $this->guessWebhookUrl());

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid webhook URL. Provide a full HTTPS URL.');

            return self::FAILURE;
        }

        try {
            $result = $telegramApiClient->setWebhook(
                $url,
                (string) config('telegram-deployer.telegram.webhook_secret', ''),
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $ok = (bool) ($result['ok'] ?? false);
        $description = (string) ($result['description'] ?? '');

        if (! $ok) {
            $this->error('Webhook registration failed: '.$description);

            return self::FAILURE;
        }

        $this->info('Webhook registered.');
        $this->line('URL: '.$url);
        $this->line('Telegram: '.$description);

        return self::SUCCESS;
    }

    private function guessWebhookUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $path = '/'.ltrim((string) config('telegram-deployer.telegram.webhook_path', '/telegram-deployer/webhook'), '/');

        return $baseUrl.$path;
    }
}
