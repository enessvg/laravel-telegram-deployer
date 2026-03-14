<?php

namespace Enessvg\LaravelTelegramDeployer\DTO;

class TelegramMessageContext
{
    public function __construct(
        public readonly string $chatId,
        public readonly ?string $userId,
        public readonly ?string $username,
        public readonly string $text,
    ) {
    }

    public static function fromUpdate(array $update): ?self
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return null;
        }

        $text = $message['text'] ?? null;
        $chatId = $message['chat']['id'] ?? null;

        if (! is_string($text) || $chatId === null) {
            return null;
        }

        $from = $message['from'] ?? [];

        return new self(
            (string) $chatId,
            isset($from['id']) ? (string) $from['id'] : null,
            isset($from['username']) ? (string) $from['username'] : null,
            $text,
        );
    }
}
