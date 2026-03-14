# enessvg/laravel-telegram-deployer

Telegram webhook tetiklemeli, TOTP doğrulamalı ve queue tabanlı deploy/action çalıştırıcı Laravel package.

## Komut formatı

Sadece şu format desteklenir:

`/run {action} {token}`

## Özellikler

- Telegram webhook secret doğrulama
- Chat/User allowlist yetkilendirme
- TOTP doğrulama (`period`, `digits`, `window`)
- Replay koruma (aynı token+window tek kullanım)
- Global single-run lock
- Fail-fast action pipeline (`artisan` + `shell`)
- DB run logları (`telegram_deployer_runs`)
- Telegram sonucu mesajlama

## Kurulum (host app)

1. Service provider'ı register et.
2. Config publish et ve `.env` doldur.
3. Migration'ları çalıştır.
4. Queue worker çalıştır.
5. Telegram webhook set et.

## Artisan command'leri

- `php artisan telegram-deployer:generate-secret`
- `php artisan telegram-deployer:generate-secret --qr` (`qrencode` varsa terminale QR basar)
- `php artisan telegram-deployer:set-webhook {url?}`

## Temel config alanları

- `telegram.bot_token`
- `telegram.webhook_path`
- `telegram.webhook_secret`
- `telegram.allowed_chat_ids`
- `telegram.allowed_user_ids`
- `otp.secret`, `otp.period`, `otp.digits`, `otp.window`
- `queue.connection`, `queue.name`
- `locks.global_lock_seconds`
- `actions`
