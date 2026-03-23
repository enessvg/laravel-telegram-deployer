<?php

namespace Enessvg\LaravelTelegramDeployer\DTO;

class RunCommand
{
    /**
     * @param array<string, string> $params
     */
    public function __construct(
        public readonly string $action,
        public readonly string $token,
        public readonly array $params = [],
    ) {
    }
}
