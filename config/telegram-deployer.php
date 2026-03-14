<?php

return [
    'route_middleware' => ['api'],

    'telegram' => [
        'bot_token' => env('TELEGRAM_DEPLOYER_BOT_TOKEN'),
        'webhook_path' => env('TELEGRAM_DEPLOYER_WEBHOOK_PATH', '/telegram-deployer/webhook'),
        'webhook_secret' => env('TELEGRAM_DEPLOYER_WEBHOOK_SECRET'),
        'allowed_chat_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_DEPLOYER_ALLOWED_CHAT_IDS', ''))))),
        'allowed_user_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_DEPLOYER_ALLOWED_USER_IDS', ''))))),
    ],

    'otp' => [
        'secret' => env('TELEGRAM_DEPLOYER_OTP_SECRET'),
        'period' => (int) env('TELEGRAM_DEPLOYER_OTP_PERIOD', 60),
        'digits' => (int) env('TELEGRAM_DEPLOYER_OTP_DIGITS', 6),
        'window' => (int) env('TELEGRAM_DEPLOYER_OTP_WINDOW', 1),
        'issuer' => env('TELEGRAM_DEPLOYER_OTP_ISSUER', 'Laravel Telegram Deployer'),
        'label' => env('TELEGRAM_DEPLOYER_OTP_LABEL', 'deploy-bot'),
    ],

    'queue' => [
        'connection' => env('TELEGRAM_DEPLOYER_QUEUE_CONNECTION'),
        'name' => env('TELEGRAM_DEPLOYER_QUEUE', 'default'),
    ],

    'locks' => [
        'global_lock_key' => env('TELEGRAM_DEPLOYER_GLOBAL_LOCK_KEY', 'telegram-deployer:global-run'),
        'global_lock_seconds' => (int) env('TELEGRAM_DEPLOYER_GLOBAL_LOCK_SECONDS', 1800),
    ],

    'runner' => [
        'default_timeout' => (int) env('TELEGRAM_DEPLOYER_STEP_TIMEOUT', 300),
        'working_directory' => env('TELEGRAM_DEPLOYER_WORKING_DIRECTORY') ?: base_path(),
    ],

    'actions' => [
        'deploy' => [
            [
                'type' => 'shell',
                'command' => 'git pull origin main',
            ],
            [
                'type' => 'shell',
                'command' => 'composer install --no-interaction --prefer-dist --no-progress',
            ],
            [
                'type' => 'artisan',
                'command' => 'migrate --force',
            ],
        ],
    ],
];
