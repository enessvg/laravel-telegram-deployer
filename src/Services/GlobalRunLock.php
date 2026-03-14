<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class GlobalRunLock
{
    public function key(): string
    {
        return (string) config('telegram-deployer.locks.global_lock_key', 'telegram-deployer:global-run');
    }

    public function seconds(): int
    {
        return (int) config('telegram-deployer.locks.global_lock_seconds', 1800);
    }

    public function probeAvailable(): bool
    {
        $lock = Cache::lock($this->key(), $this->seconds());

        if (! $lock->get()) {
            return false;
        }

        $lock->release();

        return true;
    }

    public function acquire(): ?Lock
    {
        $lock = Cache::lock($this->key(), $this->seconds());

        return $lock->get() ? $lock : null;
    }
}
