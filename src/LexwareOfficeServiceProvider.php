<?php

namespace Pirabyte\LaravelLexwareOffice;

use Illuminate\Support\ServiceProvider;

class LexwareOfficeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/lexware-office.php' => config_path('lexware-office.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lexware-office.php', 'lexware-office');
        $this->app->singleton('lexware-office', function ($app) {
            return new LexwareOffice(
                $app['config']['lexware-office.base_url'],
                $app['config']['lexware-office.api_key'],
                $app['config']['lexware-office.rate_limit_key'] ?? 'lexware_office_api',
                $app['config']['lexware-office.max_requests_per_minute'] ?? 50
            );
        });
    }
}
