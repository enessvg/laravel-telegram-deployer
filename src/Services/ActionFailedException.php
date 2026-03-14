<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use RuntimeException;
use Throwable;

class ActionFailedException extends RuntimeException
{
    /**
     * @param array<int, array<string, mixed>> $steps
     */
    public function __construct(
        string $message,
        public readonly array $steps,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
