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
];
