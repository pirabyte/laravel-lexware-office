<?php

return [
    'base_url' => env('LEXWARE_OFFICE_BASE_URL', 'https://api.lexoffice.de/v1'),
    'api_key' => env('LEXWARE_OFFICE_API_KEY'),
    'timeout' => env('LEXWARE_OFFICE_TIMEOUT', 30),
];
