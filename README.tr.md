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

1. Service provider'i register et.
2. Config publish et ve `.env` doldur.
3. Migration'lari calistir.
4. Queue worker calistir.
5. Telegram webhook set et.

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
