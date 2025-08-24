<?php

namespace Pirabyte\LaravelLexwareOffice;

use Illuminate\Support\ServiceProvider;
use Pirabyte\LaravelLexwareOffice\Classes\LexwareRateLimiter;
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

            if (!$config['enabled']) {
                return null;
            }

            $oauth2Service = new LexwareOAuth2Service(
                $config['client_id'],
                $config['client_secret'],
                $config['redirect_uri'],
                rtrim($app['config']['lexware-office.base_url'], '/v1'), // Remove /v1 for OAuth endpoints
                $config['scopes'] ?? []
            );

            // Set up token storage based on configuration
            $storageConfig = $config['token_storage'];
            if ($storageConfig['driver'] === 'database') {
                // For database storage, we'll need the user ID from the app
                // This will be set up by the developer in their app
                $tokenStorage = new CacheTokenStorage($storageConfig['cache_key']);
            } else {
                $tokenStorage = new CacheTokenStorage($storageConfig['cache_key']);
            }

            $oauth2Service->setTokenStorage($tokenStorage);

            return $oauth2Service;
        });

        // Register main LexwareOffice service
        $this->app->singleton('lexware-office', function ($app) {
            $config = $app['config']['lexware-office'];
            
            // Create the base LexwareOffice instance
            $lexwareOffice = new LexwareOffice(
                $config['base_url'],
                $config['api_key'],
                $config['rate_limiting']['rate_limit_key'] ?? 'lexware_office_api',
                $config['rate_limiting']['max_requests_per_minute'] ?? 50
            );

            // Set up advanced rate limiting if enabled
            if ($config['rate_limiting']['enabled'] ?? true) {
                $connectionId = $config['rate_limiting']['connection_id'] ?? 'default';
                $clientId = $config['rate_limiting']['client_id'] ?? 'default';
                $cachePrefix = $config['rate_limiting']['cache_prefix'] ?? 'lexware_rate_limit';
                
                $rateLimiter = new LexwareRateLimiter($connectionId, $clientId, $cachePrefix);
                $lexwareOffice->setAdvancedRateLimiter($rateLimiter);
                $lexwareOffice->useAdvancedRateLimiting(true);
            }

            // Set OAuth2 service if enabled
            if ($config['oauth2']['enabled'] ?? false) {
                $oauth2Service = $app->make('lexware-oauth2');
                if ($oauth2Service) {
                    $lexwareOffice->setOAuth2Service($oauth2Service);
                }
            }

            return $lexwareOffice;
        });
    }
}
