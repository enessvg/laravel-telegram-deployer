<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Enessvg\LaravelTelegramDeployer\DTO\RunCommand;

class RunCommandParser
{
    public function parse(string $text): ?RunCommand
    {
        $text = trim($text);

        if (! preg_match('/^\/run(?:@\w+)?\s+([A-Za-z0-9_-]+)\s+(.+)$/', $text, $matches)) {
            return null;
        }

        $action = $matches[1];
        $tail = trim($matches[2]);
        $parts = preg_split('/\s+/', $tail);

        if (! is_array($parts) || $parts === []) {
            return null;
        }

        $token = (string) array_pop($parts);

        if (! preg_match('/^\d{4,10}$/', $token)) {
            return null;
        }

        $params = [];

        foreach ($parts as $part) {
            if (! str_contains($part, '=')) {
                return null;
            }

            [$key, $value] = explode('=', $part, 2);

            if (! preg_match('/^[A-Za-z0-9_-]+$/', $key) || $value === '') {
                return null;
            }

            if (array_key_exists($key, $params)) {
                return null;
            }

            $params[$key] = $value;
        }

        return new RunCommand($action, $token, $params);
    }
}
