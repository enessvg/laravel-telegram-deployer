# enessvg/laravel-telegram-deployer

[English](./README.md) | Turkce

Telegram webhook tetiklemeli, TOTP dogrulamali ve queue tabanli deploy/action calistirici Laravel package.

## Desteklenen Surumler

- Laravel 11.x
- Laravel 12.x
- Laravel 13.x (PHP 8.3+)

## Komut Formati

Sadece su format desteklenir:

`/run {action} [key=value ...] {token}`

Ornekler:

- `/run deploy 123456`
- `/run seed class=Database\\Seeders\\UserSeeder 123456`

## Ozellikler

- Telegram webhook secret dogrulama
- Chat/User allowlist yetkilendirme
- TOTP dogrulama (`period`, `digits`, `window`)
- Replay koruma (ayni token+window tek kullanim)
- Global single-run lock
- Fail-fast action pipeline (`artisan` + `shell`)
- DB run loglari (`telegram_deployer_runs`)
- Telegram sonuc mesajlama

## Kurulum (Host App)

1. Paketi kur:

```bash
composer require enessvg/laravel-telegram-deployer
```

2. Config ve migration dosyalarini publish et:

```bash
php artisan vendor:publish --tag=telegram-deployer-config
php artisan vendor:publish --tag=telegram-deployer-migrations
```

3. `.env` dosyana su key'leri ekle (degerleri ihtiyacina gore doldur):

```dotenv
TELEGRAM_DEPLOYER_BOT_TOKEN=
TELEGRAM_DEPLOYER_WEBHOOK_PATH=
TELEGRAM_DEPLOYER_WEBHOOK_SECRET=
TELEGRAM_DEPLOYER_ALLOWED_CHAT_IDS=
TELEGRAM_DEPLOYER_ALLOWED_USER_IDS=

TELEGRAM_DEPLOYER_OTP_SECRET=
TELEGRAM_DEPLOYER_OTP_PERIOD=
TELEGRAM_DEPLOYER_OTP_DIGITS=
TELEGRAM_DEPLOYER_OTP_WINDOW=
TELEGRAM_DEPLOYER_OTP_ISSUER=
TELEGRAM_DEPLOYER_OTP_LABEL=

TELEGRAM_DEPLOYER_QUEUE_CONNECTION=
TELEGRAM_DEPLOYER_QUEUE=

TELEGRAM_DEPLOYER_GLOBAL_LOCK_KEY=
TELEGRAM_DEPLOYER_GLOBAL_LOCK_SECONDS=

TELEGRAM_DEPLOYER_STEP_TIMEOUT=
TELEGRAM_DEPLOYER_WORKING_DIRECTORY=
```

4. Migration'lari calistir:

```bash
php artisan migrate --force
```

5. Queue worker calistir.
6. Telegram webhook set et.

## Artisan Komutlari

- `php artisan telegram-deployer:generate-secret`
- `php artisan telegram-deployer:generate-secret --qr` (`qrencode` varsa terminale QR basar)
- `php artisan telegram-deployer:set-webhook {url?}`

## Temel Config Alanlari

- `telegram.bot_token`
- `telegram.webhook_path`
- `telegram.webhook_secret`
- `telegram.allowed_chat_ids`
- `telegram.allowed_user_ids`
- `otp.secret`, `otp.period`, `otp.digits`, `otp.window`
- `queue.connection`, `queue.name`
- `locks.global_lock_seconds`
- `actions`

## Parametreli Action Ornegi

```php
'actions' => [
    'seed' => [
        [
            'type' => 'artisan',
            'command' => 'db:seed --class={class} --force',
        ],
    ],
],
```
