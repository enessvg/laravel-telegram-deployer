<?php

namespace Enessvg\LaravelTelegramDeployer\Commands;

use Enessvg\LaravelTelegramDeployer\Services\TotpService;
use Illuminate\Console\Command;

class GenerateSecretCommand extends Command
{
    protected $signature = 'telegram-deployer:generate-secret
        {--length=32 : Secret length}
        {--qr : Render otpauth URI as QR in terminal (requires qrencode)}';

    protected $description = 'Generate a Base32 TOTP secret and otpauth URI for Telegram deploy commands.';

    public function handle(TotpService $totpService): int
    {
        $length = max((int) $this->option('length'), 16);
        $secret = $totpService->generateSecret($length);
        $issuer = (string) config('telegram-deployer.otp.issuer', 'Laravel Telegram Deployer');
        $label = (string) config('telegram-deployer.otp.label', 'deploy-bot');
        $period = (int) config('telegram-deployer.otp.period', 60);
        $digits = (int) config('telegram-deployer.otp.digits', 6);

        $uri = $totpService->buildOtpAuthUri($secret, $label, $issuer, $period, $digits);

        $this->line('Add this to your .env:');
        $this->line("TELEGRAM_DEPLOYER_OTP_SECRET={$secret}");
        $this->newLine();
        $this->line('otpauth URI:');
        $this->line($uri);

        if ((bool) $this->option('qr')) {
            $this->renderTerminalQr($uri);
        }

        return self::SUCCESS;
    }

    private function renderTerminalQr(string $uri): void
    {
        if (! $this->hasQrencodeBinary()) {
            $this->newLine();
            $this->warn('Terminal QR requested but `qrencode` binary was not found.');
            $this->line('Install tip: `apt install qrencode` or `brew install qrencode`');

            return;
        }

        $output = [];
        $exitCode = 1;

        @exec(
            sprintf('qrencode -t ANSIUTF8 %s 2>/dev/null', escapeshellarg($uri)),
            $output,
            $exitCode,
        );

        if ($exitCode !== 0 || $output === []) {
            $this->newLine();
            $this->warn('Unable to render terminal QR with `qrencode`.');

            return;
        }

        $this->newLine();
        $this->line('Terminal QR:');

        foreach ($output as $line) {
            $this->line($line);
        }
    }

    private function hasQrencodeBinary(): bool
    {
        $checkCommand = PHP_OS_FAMILY === 'Windows'
            ? 'where qrencode'
            : 'command -v qrencode';

        $output = [];
        $exitCode = 1;

        @exec($checkCommand.' 2>/dev/null', $output, $exitCode);

        return $exitCode === 0 && $output !== [];
    }
}
