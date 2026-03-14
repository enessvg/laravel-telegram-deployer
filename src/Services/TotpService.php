<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use RuntimeException;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    public function buildOtpAuthUri(string $secret, string $label, string $issuer, int $period, int $digits): string
    {
        $labelValue = rawurlencode($issuer.':'.$label);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&period=%d&digits=%d',
            $labelValue,
            strtoupper($secret),
            rawurlencode($issuer),
            $period,
            $digits,
        );
    }

    public function verify(string $token, ?int $timestamp = null): ?int
    {
        $secret = (string) config('telegram-deployer.otp.secret');
        $period = (int) config('telegram-deployer.otp.period', 60);
        $digits = (int) config('telegram-deployer.otp.digits', 6);
        $window = (int) config('telegram-deployer.otp.window', 1);

        if ($secret === '') {
            throw new RuntimeException('TELEGRAM_DEPLOYER_OTP_SECRET is not configured.');
        }

        $timestamp ??= time();
        $counter = (int) floor($timestamp / $period);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidateCounter = $counter + $offset;
            if ($candidateCounter < 0) {
                continue;
            }

            if (hash_equals($this->tokenForCounter($secret, $candidateCounter, $digits), $token)) {
                return $candidateCounter;
            }
        }

        return null;
    }

    public function tokenForCounter(string $secret, int $counter, int $digits): string
    {
        $key = $this->decodeBase32($secret);
        $counterBytes = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $counterBytes, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % (10 ** $digits);

        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $encoded): string
    {
        $encoded = strtoupper(str_replace('=', '', preg_replace('/\s+/', '', $encoded)));

        if ($encoded === '') {
            throw new RuntimeException('TOTP secret cannot be empty.');
        }

        if (! preg_match('/^[A-Z2-7]+$/', $encoded)) {
            throw new RuntimeException('TOTP secret is not valid base32.');
        }

        $bits = '';
        $length = strlen($encoded);

        for ($i = 0; $i < $length; $i++) {
            $value = strpos(self::BASE32_ALPHABET, $encoded[$i]);
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        $bitLength = strlen($bits);

        for ($i = 0; $i + 8 <= $bitLength; $i += 8) {
            $binary .= chr(bindec(substr($bits, $i, 8)));
        }

        return $binary;
    }
}
