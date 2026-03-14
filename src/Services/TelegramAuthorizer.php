<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

class TelegramAuthorizer
{
    public function isAllowed(string $chatId, ?string $userId): bool
    {
        $allowedChatIds = array_map('strval', (array) config('telegram-deployer.telegram.allowed_chat_ids', []));
        $allowedUserIds = array_map('strval', (array) config('telegram-deployer.telegram.allowed_user_ids', []));

        $chatAllowed = empty($allowedChatIds) || in_array((string) $chatId, $allowedChatIds, true);
        $userAllowed = empty($allowedUserIds) || ($userId !== null && in_array((string) $userId, $allowedUserIds, true));

        return $chatAllowed && $userAllowed;
    }
}
