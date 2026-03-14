<?php

namespace Enessvg\LaravelTelegramDeployer;

use Enessvg\LaravelTelegramDeployer\Commands\GenerateSecretCommand;
use Enessvg\LaravelTelegramDeployer\Commands\SetWebhookCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TelegramDeployerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/telegram-deployer.php', 'telegram-deployer');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/telegram-deployer.php' => config_path('telegram-deployer.php'),
            ], 'telegram-deployer-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'telegram-deployer-migrations');

            $this->commands([
                GenerateSecretCommand::class,
                SetWebhookCommand::class,
            ]);
        }

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $path = ltrim((string) config('telegram-deployer.telegram.webhook_path', '/telegram-deployer/webhook'), '/');
        $middleware = config('telegram-deployer.route_middleware', ['api']);

        Route::middleware($middleware)
            ->post($path, \Enessvg\LaravelTelegramDeployer\Http\Controllers\TelegramWebhookController::class)
            ->name('telegram-deployer.webhook');
    }
}
