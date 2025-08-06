<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lexware Office API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and configuration for the
    | Lexware Office API integration. This package supports all API endpoints
    | including standard endpoints and partner-specific endpoints.
    |
    */

    // API Credentials and Connection Settings
    'base_url' => env('LEXWARE_OFFICE_BASE_URL', 'https://api.lexoffice.de/v1'),
    'api_key' => env('LEXWARE_OFFICE_API_KEY'),
    'timeout' => env('LEXWARE_OFFICE_TIMEOUT', 30),
    
    // Rate Limiting Configuration
    'rate_limiting' => [
        // Legacy rate limiting (for backward compatibility)
        'rate_limit_key' => env('LEXWARE_OFFICE_RATE_LIMIT_KEY', 'lexware_office_api'),
        'max_requests_per_minute' => env('LEXWARE_OFFICE_MAX_REQUESTS', 50),
        
        // New Lexware Office Rate Limiting (Token Bucket Algorithm)
        'enabled' => env('LEXWARE_OFFICE_ADVANCED_RATE_LIMITING', true),
        'cache_prefix' => env('LEXWARE_OFFICE_RATE_LIMIT_PREFIX', 'lexware_rate_limit'),
        
        // Connection-based rate limiting (per connection and endpoint)
        'connection' => [
            'requests_per_second' => 2,
            'burst_size' => 5,
        ],
        
        // Client-based rate limiting (per API client and endpoint)
        'client' => [
            'requests_per_second' => 5,
            'burst_size' => 5,
        ],
        
        // Identifiers for rate limiting
        'connection_id' => env('LEXWARE_OFFICE_CONNECTION_ID'), // Should be set per connection/organization
        'client_id' => env('LEXWARE_OFFICE_CLIENT_ID'), // Should be set per API client
    ],
    
    // OAuth2 Configuration (optional, for automatic token management)
    'oauth2' => [
        'enabled' => env('LEXWARE_OFFICE_OAUTH2_ENABLED', false),
        'client_id' => env('LEXWARE_OFFICE_OAUTH2_CLIENT_ID'),
        'client_secret' => env('LEXWARE_OFFICE_OAUTH2_CLIENT_SECRET'),
        'redirect_uri' => env('LEXWARE_OFFICE_OAUTH2_REDIRECT_URI'),
        'scopes' => [
            // Add required scopes here, e.g.:
            // 'profile', 'contacts', 'vouchers', 'financial_accounts'
        ],
        
        // Token Storage Configuration
        'token_storage' => [
            'driver' => env('LEXWARE_OFFICE_TOKEN_STORAGE', 'cache'), // 'cache' or 'database'
            'cache_key' => 'lexware_office_token',
            'database_table' => 'lexware_tokens',
            'user_column' => 'user_id',
        ],
    ],
];
