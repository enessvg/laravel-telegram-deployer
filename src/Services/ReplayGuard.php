<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Illuminate\Support\Facades\Cache;

class ReplayGuard
{
    public function consume(string $token, int $counter, string $action): bool
    {
        $period = (int) config('telegram-deployer.otp.period', 60);
        $ttlSeconds = max($period * 2, 60);
        $key = sprintf('telegram-deployer:otp:%d:%s:%s', $counter, $token, $action);

        return Cache::add($key, 1, now()->addSeconds($ttlSeconds));
    }
}
