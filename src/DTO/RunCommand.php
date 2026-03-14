<?php

namespace Enessvg\LaravelTelegramDeployer\DTO;

class RunCommand
{
    public function __construct(
        public readonly string $action,
        public readonly string $token,
    ) {
    }
}
