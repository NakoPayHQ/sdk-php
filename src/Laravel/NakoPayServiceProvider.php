<?php

declare(strict_types=1);

namespace NakoPay\Laravel;

use Illuminate\Support\ServiceProvider;
use NakoPay\Client;

class NakoPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/nakopay.php', 'nakopay');

        $this->app->singleton(Client::class, function ($app) {
            $cfg = $app['config']['nakopay'] ?? [];
            return new Client([
                'api_key' => $cfg['api_key'] ?? null,
                'base_url' => $cfg['base_url'] ?? Client::DEFAULT_BASE_URL,
                'api_version' => $cfg['api_version'] ?? Client::DEFAULT_API_VERSION,
                'timeout' => (float) ($cfg['timeout'] ?? 30.0),
                'max_retries' => (int) ($cfg['max_retries'] ?? 3),
            ]);
        });

        $this->app->alias(Client::class, 'nakopay');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/nakopay.php' => $this->app->configPath('nakopay.php'),
            ], 'nakopay-config');
        }
    }
}
