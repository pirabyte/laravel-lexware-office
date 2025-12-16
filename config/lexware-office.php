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

    // Rate Limiting
    'rate_limit_key' => env('LEXWARE_OFFICE_RATE_LIMIT_KEY', 'lexware_office_api'),
    'max_requests_per_minute' => env('LEXWARE_OFFICE_MAX_REQUESTS', 50),

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
            // Used only when binding a single global oauth2 service via the ServiceProvider.
            // For per-user OAuth2, use LexwareOfficeFactory::forUser() which supplies the real user ID.
            'database_user_id' => 0,
        ],
    ],
];
