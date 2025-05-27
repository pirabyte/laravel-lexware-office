<?php

namespace Pirabyte\LaravelLexwareOffice;

use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\DatabaseTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareTokenStorage;

class LexwareOfficeFactory
{
    /**
     * Create a LexwareOffice instance for a specific user with OAuth2
     * 
     * @param mixed $userId User identifier for token storage
     * @param string|null $clientId OAuth2 client ID (defaults to config)
     * @param string|null $clientSecret OAuth2 client secret (defaults to config) 
     * @param string|null $redirectUri OAuth2 redirect URI (defaults to config)
     * @param array|null $scopes OAuth2 scopes (defaults to config)
     * @param string $tokenStorage Token storage driver ('database' or 'cache')
     * @return LexwareOffice
     */
    public static function forUser(
        mixed $userId,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null,
        ?array $scopes = null,
        string $tokenStorage = 'database'
    ): LexwareOffice {
        // Use config values as defaults
        $clientId = $clientId ?: config('lexware-office.oauth2.client_id');
        $clientSecret = $clientSecret ?: config('lexware-office.oauth2.client_secret');
        $redirectUri = $redirectUri ?: config('lexware-office.oauth2.redirect_uri');
        $scopes = $scopes ?: config('lexware-office.oauth2.scopes', []);
        
        // Create LexwareOffice instance
        $lexwareOffice = new LexwareOffice(
            config('lexware-office.base_url'),
            config('lexware-office.api_key'), // Fallback API key
            config('lexware-office.rate_limit_key', 'lexware_office_api') . '_user_' . $userId,
            config('lexware-office.max_requests_per_minute', 50)
        );
        
        // Create OAuth2 service if credentials are available
        if ($clientId && $clientSecret && $redirectUri) {
            $oauth2Service = new LexwareOAuth2Service(
                $clientId,
                $clientSecret,
                $redirectUri,
                rtrim(config('lexware-office.base_url'), '/v1'), // Remove /v1 for OAuth endpoints
                $scopes
            );
            
            // Set up user-specific token storage
            $tokenStorageInstance = self::createTokenStorage($userId, $tokenStorage);
            $oauth2Service->setTokenStorage($tokenStorageInstance);
            
            $lexwareOffice->setOAuth2Service($oauth2Service);
        }
        
        return $lexwareOffice;
    }
    
    /**
     * Create a LexwareOffice instance with static API key (no OAuth2)
     * 
     * @param string $apiKey Static API key
     * @param mixed|null $userId Optional user identifier for rate limiting
     * @return LexwareOffice
     */
    public static function withApiKey(string $apiKey, mixed $userId = null): LexwareOffice
    {
        $rateLimitKey = config('lexware-office.rate_limit_key', 'lexware_office_api');
        if ($userId) {
            $rateLimitKey .= '_user_' . $userId;
        }
        
        return new LexwareOffice(
            config('lexware-office.base_url'),
            $apiKey,
            $rateLimitKey,
            config('lexware-office.max_requests_per_minute', 50)
        );
    }
    
    /**
     * Create OAuth2 service without LexwareOffice instance
     * Useful for handling authorization flow before creating main instance
     * 
     * @param mixed $userId User identifier for token storage
     * @param string|null $clientId OAuth2 client ID (defaults to config)
     * @param string|null $clientSecret OAuth2 client secret (defaults to config)
     * @param string|null $redirectUri OAuth2 redirect URI (defaults to config)
     * @param array|null $scopes OAuth2 scopes (defaults to config)
     * @param string $tokenStorage Token storage driver ('database' or 'cache')
     * @return LexwareOAuth2Service
     */
    public static function createOAuth2Service(
        mixed $userId,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null,
        ?array $scopes = null,
        string $tokenStorage = 'database'
    ): LexwareOAuth2Service {
        $clientId = $clientId ?: config('lexware-office.oauth2.client_id');
        $clientSecret = $clientSecret ?: config('lexware-office.oauth2.client_secret');
        $redirectUri = $redirectUri ?: config('lexware-office.oauth2.redirect_uri');
        $scopes = $scopes ?: config('lexware-office.oauth2.scopes', []);
        
        $oauth2Service = new LexwareOAuth2Service(
            $clientId,
            $clientSecret,
            $redirectUri,
            rtrim(config('lexware-office.base_url'), '/v1'),
            $scopes
        );
        
        $tokenStorageInstance = self::createTokenStorage($userId, $tokenStorage);
        $oauth2Service->setTokenStorage($tokenStorageInstance);
        
        return $oauth2Service;
    }
    
    /**
     * Create token storage instance based on driver
     * 
     * @param mixed $userId User identifier
     * @param string $driver Storage driver ('database' or 'cache')
     * @return LexwareTokenStorage
     */
    protected static function createTokenStorage(mixed $userId, string $driver): LexwareTokenStorage
    {
        return match ($driver) {
            'database' => new DatabaseTokenStorage(
                $userId,
                config('lexware-office.oauth2.token_storage.database_table', 'lexware_tokens'),
                config('lexware-office.oauth2.token_storage.user_column', 'user_id')
            ),
            'cache' => new CacheTokenStorage(
                config('lexware-office.oauth2.token_storage.cache_key', 'lexware_office_token') . '_user_' . $userId
            ),
            default => throw new \InvalidArgumentException("Unsupported token storage driver: {$driver}")
        };
    }
}