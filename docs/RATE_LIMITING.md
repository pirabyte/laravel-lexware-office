# Rate Limiting Documentation

## Overview

The Laravel Lexware Office package now includes advanced rate limiting that complies with Lexware Office API requirements. The implementation uses a token bucket algorithm to provide precise rate limiting per connection and endpoint.

## Lexware Office API Rate Limits

According to Lexware Office API documentation:

- **Connection/Endpoint Limit**: 2 requests per second (burst size: 5)
- **Client/Endpoint Limit**: 5 requests per second (burst size: 5)

Rate limits are applied per endpoint, meaning each API endpoint has its own rate limit bucket.

## Configuration

Add the following to your `.env` file:

```env
# Basic configuration
LEXWARE_OFFICE_BASE_URL=https://api.lexoffice.de/v1
LEXWARE_OFFICE_API_KEY=your-api-key

# Advanced rate limiting (recommended)
LEXWARE_OFFICE_ADVANCED_RATE_LIMITING=true
LEXWARE_OFFICE_CONNECTION_ID=your-organization-id
LEXWARE_OFFICE_CLIENT_ID=your-application-id
LEXWARE_OFFICE_RATE_LIMIT_PREFIX=lexware_rate_limit

# Legacy rate limiting (backward compatibility)
LEXWARE_OFFICE_RATE_LIMIT_KEY=lexware_office_api
LEXWARE_OFFICE_MAX_REQUESTS=50
```

## Usage

### Automatic Configuration (Laravel)

The service provider automatically configures rate limiting when the package is installed:

```php
// The LexwareOffice facade will automatically use advanced rate limiting
$contacts = LexwareOffice::get('contacts');
```

### Manual Configuration

```php
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Classes\LexwareRateLimiter;

// Create rate limiter
$rateLimiter = new LexwareRateLimiter(
    'connection-id',  // Unique per organization/connection
    'client-id',      // Unique per API client application
    'cache-prefix'    // Optional cache prefix
);

// Create LexwareOffice instance
$lexwareOffice = new LexwareOffice($baseUrl, $apiKey);

// Enable advanced rate limiting
$lexwareOffice->setAdvancedRateLimiter($rateLimiter);
$lexwareOffice->useAdvancedRateLimiting(true);
```

## Rate Limiting Behavior

### Endpoint Separation

Each API endpoint has separate rate limits:

```php
// These requests have separate rate limits
$contacts = LexwareOffice::get('contacts');     // contacts endpoint
$vouchers = LexwareOffice::get('vouchers');     // vouchers endpoint
$profile = LexwareOffice::get('profile');       // profile endpoint
```

### ID Normalization

The rate limiter normalizes endpoints with IDs:

```php
// These are treated as the same endpoint
$contact1 = LexwareOffice::get('contacts/12345');
$contact2 = LexwareOffice::get('contacts/67890');
$contact3 = LexwareOffice::get('contacts/abc-def-123');

// Query parameters are ignored
$contacts1 = LexwareOffice::get('contacts?page=1&size=10');
$contacts2 = LexwareOffice::get('contacts?page=2&size=20');
```

### Rate Limit Hierarchy

The system checks both connection and client limits:

1. **Connection Limit**: 2 requests/second per connection and endpoint
2. **Client Limit**: 5 requests/second per client and endpoint

If either limit is exceeded, the request is blocked.

## Error Handling

When rate limits are exceeded, a `LexwareOfficeApiException` is thrown:

```php
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

try {
    $contacts = LexwareOffice::get('contacts');
} catch (LexwareOfficeApiException $e) {
    if ($e->getCode() === LexwareOfficeApiException::STATUS_RATE_LIMITED) {
        $errorData = json_decode($e->getMessage(), true);
        
        echo "Rate limit exceeded: " . $errorData['details'];
        echo "Wait time: " . $errorData['retryAfter'] . " seconds";
        echo "Limit type: " . $errorData['limitType']; // 'connection' or 'client'
        
        // Wait and retry
        sleep($errorData['retryAfter']);
        $contacts = LexwareOffice::get('contacts');
    }
}
```

## Monitoring and Debugging

### Check Rate Limiter Status

```php
$status = LexwareOffice::getRateLimiterStatus('contacts');

echo "Connection Limit:";
echo "  Tokens remaining: " . $status['connection']['tokens'];
echo "  Rate limit: " . $status['connection']['limit'] . " req/s";
echo "  Burst size: " . $status['connection']['burst'];

echo "Client Limit:";
echo "  Tokens remaining: " . $status['client']['tokens'];
echo "  Rate limit: " . $status['client']['limit'] . " req/s";
echo "  Burst size: " . $status['client']['burst'];
```

### Legacy Rate Limiting Status

For backward compatibility:

```php
// Without advanced rate limiting
$lexwareOffice = new LexwareOffice($baseUrl, $apiKey);
$status = $lexwareOffice->getRateLimiterStatus();

echo "Legacy rate limiter:";
echo "  Key: " . $status['legacy']['key'];
echo "  Max per minute: " . $status['legacy']['max_per_minute'];
echo "  Remaining: " . $status['legacy']['remaining'];
```

## Migration from Legacy Rate Limiting

### Automatic Migration

The package automatically uses advanced rate limiting when:
- `LEXWARE_OFFICE_ADVANCED_RATE_LIMITING=true` (default)
- Connection ID and Client ID are configured

### Manual Migration

```php
// Old way (still supported)
$lexwareOffice = new LexwareOffice($baseUrl, $apiKey, 'rate-key', 50);

// New way
$rateLimiter = new LexwareRateLimiter('connection-id', 'client-id');
$lexwareOffice = new LexwareOffice($baseUrl, $apiKey);
$lexwareOffice->setAdvancedRateLimiter($rateLimiter);
$lexwareOffice->useAdvancedRateLimiting(true);
```

## Best Practices

### 1. Use Unique Identifiers

- **Connection ID**: Use organization/tenant ID
- **Client ID**: Use application/service identifier

```php
$connectionId = 'org-' . $organizationId;
$clientId = 'my-app-v1.0';
```

### 2. Handle Rate Limits Gracefully

```php
private function makeApiRequest($endpoint, $maxRetries = 3)
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return LexwareOffice::get($endpoint);
        } catch (LexwareOfficeApiException $e) {
            if ($e->getCode() === LexwareOfficeApiException::STATUS_RATE_LIMITED) {
                $errorData = json_decode($e->getMessage(), true);
                $waitTime = $errorData['retryAfter'];
                
                if ($attempt < $maxRetries - 1) {
                    sleep($waitTime);
                    $attempt++;
                    continue;
                }
            }
            throw $e;
        }
    }
}
```

### 3. Monitor Rate Limits

Implement monitoring to track rate limit usage:

```php
$status = LexwareOffice::getRateLimiterStatus($endpoint);
if ($status['connection']['tokens'] < 2) {
    // Log warning: approaching connection rate limit
}
```

### 4. Use Redis for Production

Configure Redis as your cache driver for better performance and persistence:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Configuration Reference

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `LEXWARE_OFFICE_ADVANCED_RATE_LIMITING` | `true` | Enable advanced rate limiting |
| `LEXWARE_OFFICE_CONNECTION_ID` | Required | Unique connection identifier |
| `LEXWARE_OFFICE_CLIENT_ID` | Required | Unique client identifier |
| `LEXWARE_OFFICE_RATE_LIMIT_PREFIX` | `lexware_rate_limit` | Cache key prefix |

### Config File Options

```php
// config/lexware-office.php
'rate_limiting' => [
    'enabled' => env('LEXWARE_OFFICE_ADVANCED_RATE_LIMITING', true),
    'cache_prefix' => env('LEXWARE_OFFICE_RATE_LIMIT_PREFIX', 'lexware_rate_limit'),
    
    'connection' => [
        'requests_per_second' => 2,
        'burst_size' => 5,
    ],
    
    'client' => [
        'requests_per_second' => 5,
        'burst_size' => 5,
    ],
    
    'connection_id' => env('LEXWARE_OFFICE_CONNECTION_ID'),
    'client_id' => env('LEXWARE_OFFICE_CLIENT_ID'),
],
```

## Examples

See `examples/rate-limiting-example.php` for a complete working example.

## Troubleshooting

### Common Issues

1. **Rate limits still using legacy**: Ensure connection_id and client_id are set
2. **Cache not persisting**: Configure Redis or database cache driver
3. **Unexpected rate limiting**: Check endpoint normalization and ID patterns

### Debug Mode

Enable debug mode to see detailed rate limiting information:

```php
$status = LexwareOffice::getRateLimiterStatus($endpoint);
var_dump($status);
```