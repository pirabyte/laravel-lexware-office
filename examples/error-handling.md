# Error Handling in Lexware Office Integration

This guide demonstrates how to handle different error types returned by the Lexware Office API.

## Error Types

The Lexware Office API can return several types of errors, including:

1. **Authentication Errors (401)** - Invalid or expired API key
2. **Validation Errors (400)** - Invalid input data
3. **Not Found Errors (404)** - Requested resource doesn't exist
4. **Rate Limit Errors (429)** - Too many requests in a given time period
5. **Server Errors (500, 502, 503)** - Internal server issues

## Basic Error Handling

All errors from the Lexware Office API are wrapped in a `LexwareOfficeApiException` which provides detailed information about the error:

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

try {
    $contact = LexwareOffice::contacts()->get('invalid-id');
} catch (LexwareOfficeApiException $e) {
    // Get basic error information
    $message = $e->getMessage();
    $statusCode = $e->getStatusCode();
    $errorType = $e->getErrorType();
    
    // Get detailed response data
    $responseData = $e->getResponseData();
    
    // Handle the error appropriately
    // ...
}
```

## Handling Specific Error Types

The exception provides helper methods to identify different error types:

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

try {
    // Some API operation
    $result = LexwareOffice::contacts()->create($invalidContact);
} catch (LexwareOfficeApiException $e) {
    if ($e->isAuthError()) {
        // Handle authentication errors (401)
        // Typically means your API key is invalid or expired
        handleAuthError($e);
    } elseif ($e->isValidationError()) {
        // Handle validation errors (400)
        // When request data doesn't meet requirements
        handleValidationError($e);
    } elseif ($e->isNotFoundError()) {
        // Handle not found errors (404)
        // When the requested resource doesn't exist
        handleNotFoundError($e);
    } elseif ($e->isRateLimitError()) {
        // Handle rate limit errors (429)
        // When too many requests are made in a short time
        handleRateLimitError($e);
    } elseif ($e->isServerError()) {
        // Handle server errors (500, 502, 503)
        // When the API has internal server issues
        handleServerError($e);
    } else {
        // Handle other unexpected errors
        handleUnknownError($e);
    }
}
```

## Handling Rate Limiting

Rate limit errors (429) include information about when you can retry:

```php
try {
    $profile = LexwareOffice::profile()->get();
} catch (LexwareOfficeApiException $e) {
    if ($e->isRateLimitError()) {
        // Get the recommended retry-after time in seconds
        $retryAfter = $e->getRetryAfter() ?? 60;
        
        // Get a human-readable retry suggestion
        $retrySuggestion = $e->getRetrySuggestion();
        
        // Log the rate limit error
        Log::warning("Lexware Office API rate limit exceeded. {$retrySuggestion}");
        
        // Implement exponential backoff and retry
        // e.g., wait for $retryAfter seconds and then retry
    }
}
```

## Handling Validation Errors

Validation errors typically include details about which fields failed validation:

```php
try {
    $contact = LexwareOffice::contacts()->create($newContact);
} catch (LexwareOfficeApiException $e) {
    if ($e->isValidationError()) {
        $responseData = $e->getResponseData();
        
        // Check if validation error details are available
        if (isset($responseData['validationErrors'])) {
            foreach ($responseData['validationErrors'] as $error) {
                $field = $error['field'] ?? 'unknown';
                $errorMsg = $error['message'] ?? 'Unknown validation error';
                
                // Handle each validation error
                Log::warning("Validation error for field {$field}: {$errorMsg}");
            }
        }
    }
}
```

## Constants for Error Types

The `LexwareOfficeApiException` class provides constants for status codes and error types:

```php
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

// Status code constants
LexwareOfficeApiException::STATUS_BAD_REQUEST;      // 400
LexwareOfficeApiException::STATUS_UNAUTHORIZED;     // 401
LexwareOfficeApiException::STATUS_NOT_FOUND;        // 404
LexwareOfficeApiException::STATUS_RATE_LIMITED;     // 429
LexwareOfficeApiException::STATUS_SERVER_ERROR;     // 500

// Error type constants
LexwareOfficeApiException::ERROR_TYPE_AUTHORIZATION;     // 'AuthorizationException'
LexwareOfficeApiException::ERROR_TYPE_VALIDATION;        // 'ValidationException'
LexwareOfficeApiException::ERROR_TYPE_RESOURCE_NOT_FOUND; // 'ResourceNotFoundException'
LexwareOfficeApiException::ERROR_TYPE_RATE_LIMIT;        // 'RateLimitException'
LexwareOfficeApiException::ERROR_TYPE_SERVER_ERROR;      // 'ServerException'
```

## Recommended Retry Strategy for Rate Limiting

When handling rate limit errors, it's recommended to implement an exponential backoff strategy:

```php
function callApiWithRetry(callable $apiCall, int $maxRetries = 3)
{
    $attempt = 0;
    
    while ($attempt <= $maxRetries) {
        try {
            return $apiCall();
        } catch (LexwareOfficeApiException $e) {
            $attempt++;
            
            if (!$e->isRateLimitError() || $attempt > $maxRetries) {
                throw $e; // Re-throw if not a rate limit error or max retries reached
            }
            
            // Get retry time with exponential backoff
            $retryAfter = $e->getRetryAfter() ?? 60;
            $waitTime = $retryAfter * (2 ** ($attempt - 1)); // Exponential backoff
            
            Log::info("Rate limit hit. Retrying in {$waitTime} seconds (attempt {$attempt}/{$maxRetries})");
            sleep($waitTime);
        }
    }
}

// Usage example
$contact = callApiWithRetry(function() {
    return LexwareOffice::contacts()->get('some-id');
});
```