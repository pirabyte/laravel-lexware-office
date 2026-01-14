<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice;

use Illuminate\Support\ServiceProvider;
use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\DatabaseTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;

class LexwareOfficeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/lexware-office.php' => config_path('lexware-office.php'),
        ], 'lexware-office-config');

        // Publish migration
        $this->publishes([
            __DIR__.'/../database/migrations/create_lexware_tokens_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_lexware_tokens_table.php'),
        ], 'lexware-office-migration');

        // Publish both config and migration together
        $this->publishes([
            __DIR__.'/../config/lexware-office.php' => config_path('lexware-office.php'),
            __DIR__.'/../database/migrations/create_lexware_tokens_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_lexware_tokens_table.php'),
        ], 'lexware-office');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lexware-office.php', 'lexware-office');

        // Register OAuth2 service
        $this->app->singleton('lexware-oauth2', function ($app) {
            $config = $app['config']['lexware-office.oauth2'];

            if (! $config['enabled']) {
                return null;
            }

            $oauthBaseUrl = self::removeV1Suffix($app['config']['lexware-office.base_url']);

            $oauth2Service = new LexwareOAuth2Service(
                $config['client_id'],
                $config['client_secret'],
                $config['redirect_uri'],
                $oauthBaseUrl,
                $config['scopes'] ?? []
            );

            // Set up token storage based on configuration
            $storageConfig = $config['token_storage'];
            $driver = $storageConfig['driver'] ?? 'cache';
            $tokenStorage = match ($driver) {
                'database' => new DatabaseTokenStorage(
                    $storageConfig['database_user_id'] ?? 0,
                    $storageConfig['database_table'] ?? 'lexware_tokens',
                    $storageConfig['user_column'] ?? 'user_id'
                ),
                default => new CacheTokenStorage($storageConfig['cache_key'] ?? 'lexware_office_token'),
            };

            $oauth2Service->setTokenStorage($tokenStorage);

            return $oauth2Service;
        });

        // Register main LexwareOffice service
        $this->app->singleton('lexware-office', function ($app) {
            $lexwareOffice = new LexwareOffice(
                $app['config']['lexware-office.base_url'],
                $app['config']['lexware-office.api_key'],
                $app['config']['lexware-office.rate_limit_key'] ?? 'lexware_office_api',
                $app['config']['lexware-office.max_requests_per_minute'] ?? 50,
                (float) ($app['config']['lexware-office.timeout'] ?? 30)
            );

            // Set OAuth2 service if enabled
            if ($app['config']['lexware-office.oauth2.enabled']) {
                $oauth2Service = $app->make('lexware-oauth2');
                if ($oauth2Service) {
                    $lexwareOffice->setOAuth2Service($oauth2Service);
                }
            }

            return $lexwareOffice;
        });
    }

    private static function removeV1Suffix(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        return preg_replace('~/v1$~', '', $baseUrl) ?: $baseUrl;
    }
}
