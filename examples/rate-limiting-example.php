<?php

/**
 * Example: Using the New Lexware Office Rate Limiter
 * 
 * This example demonstrates how to use the improved rate limiting functionality
 * that complies with Lexware Office API requirements.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Classes\LexwareRateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

// Configuration
$baseUrl = 'https://api.lexoffice.de/v1';
$apiKey = 'your-api-key-here';
$connectionId = 'organization-123'; // Unique per organization/connection
$clientId = 'my-app-client'; // Unique per API client

// Create the rate limiter
$rateLimiter = new LexwareRateLimiter($connectionId, $clientId);

// Create LexwareOffice instance
$lexwareOffice = new LexwareOffice($baseUrl, $apiKey);

// Enable advanced rate limiting
$lexwareOffice->setAdvancedRateLimiter($rateLimiter);
$lexwareOffice->useAdvancedRateLimiting(true);

// Example 1: Basic usage with rate limiting
try {
    echo "Making requests to contacts endpoint...\n";
    
    for ($i = 1; $i <= 7; $i++) {
        try {
            $contacts = $lexwareOffice->get('contacts');
            echo "Request $i successful\n";
        } catch (LexwareOfficeApiException $e) {
            if ($e->getCode() === LexwareOfficeApiException::STATUS_RATE_LIMITED) {
                $errorData = json_decode($e->getMessage(), true);
                echo "Request $i rate limited: {$errorData['details']}\n";
                echo "Wait time: {$errorData['retryAfter']} seconds\n";
                echo "Limit type: {$errorData['limitType']}\n";
                
                // In a real application, you might wait and retry
                // sleep($errorData['retryAfter']);
                break;
            } else {
                throw $e;
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example 2: Check rate limiter status
echo "\nRate Limiter Status:\n";
$status = $lexwareOffice->getRateLimiterStatus('contacts');

echo "Connection Limit:\n";
echo "  Tokens remaining: {$status['connection']['tokens']}\n";
echo "  Rate limit: {$status['connection']['limit']} req/s\n";
echo "  Burst size: {$status['connection']['burst']}\n";

echo "Client Limit:\n";
echo "  Tokens remaining: {$status['client']['tokens']}\n";
echo "  Rate limit: {$status['client']['limit']} req/s\n";
echo "  Burst size: {$status['client']['burst']}\n";

// Example 3: Different endpoints have separate limits
echo "\nTesting different endpoints...\n";

try {
    // Try vouchers endpoint (should work even if contacts is rate limited)
    $vouchers = $lexwareOffice->get('vouchers');
    echo "Vouchers request successful\n";
    
    // Try profile endpoint
    $profile = $lexwareOffice->get('profile');
    echo "Profile request successful\n";
    
} catch (LexwareOfficeApiException $e) {
    if ($e->getCode() === LexwareOfficeApiException::STATUS_RATE_LIMITED) {
        $errorData = json_decode($e->getMessage(), true);
        echo "Rate limited: {$errorData['details']}\n";
    } else {
        echo "API Error: " . $e->getMessage() . "\n";
    }
}

// Example 4: Legacy rate limiting (backward compatibility)
echo "\nTesting legacy rate limiting...\n";

$legacyLexwareOffice = new LexwareOffice($baseUrl, $apiKey, 'legacy-key', 10);
// Don't set advanced rate limiter - uses legacy by default

$status = $legacyLexwareOffice->getRateLimiterStatus();
echo "Legacy rate limiter status:\n";
echo "  Key: {$status['legacy']['key']}\n";
echo "  Max per minute: {$status['legacy']['max_per_minute']}\n";
echo "  Remaining: {$status['legacy']['remaining']}\n";

echo "\nExample completed!\n";