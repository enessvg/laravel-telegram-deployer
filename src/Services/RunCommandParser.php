<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Enessvg\LaravelTelegramDeployer\DTO\RunCommand;

class RunCommandParser
{
    public function parse(string $text): ?RunCommand
    {
        $text = trim($text);

        if (! preg_match('/^\/run(?:@\w+)?\s+([A-Za-z0-9_-]+)\s+(\d{4,10})$/', $text, $matches)) {
            return null;
        }

        return new RunCommand($matches[1], $matches[2]);
    }
}
