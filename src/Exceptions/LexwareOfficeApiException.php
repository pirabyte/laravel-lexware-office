<?php

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;

class LexwareOfficeApiException extends Exception
{
    // Standard error status codes from the API
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_CONFLICT = 409;
    public const STATUS_RATE_LIMITED = 429;
    public const STATUS_SERVER_ERROR = 500;

    // Error types from the API (using actual AWS API Gateway error types)
    public const ERROR_TYPE_AUTHORIZATION = 'UnauthorizedException';
    public const ERROR_TYPE_VALIDATION = 'ValidationException';
    public const ERROR_TYPE_CONFLICT = 'ConflictException';
    public const ERROR_TYPE_RATE_LIMIT = 'ThrottlingException';
    public const ERROR_TYPE_RESOURCE_NOT_FOUND = 'ResourceNotFoundException';
    public const ERROR_TYPE_SERVER_ERROR = 'InternalServerException';

    /**
     * HTTP status code from the API response
     */
    protected int $statusCode;

    /**
     * Raw decoded response data from the API
     */
    protected array $responseData;

    /**
     * Error type from the API response
     */
    protected ?string $errorType = null;

    /**
     * Retry-After value for rate limit errors
     */
    protected ?int $retryAfter = null;

    /**
     * Original request exception if available
     */
    protected ?RequestException $requestException = null;

    /**
     * Create a new Lexware Office API exception
     *
     * @param string $message The error message or raw response body
     * @param int $statusCode The HTTP status code
     * @param RequestException|null $previous The previous exception
     */
    public function __construct($message, $statusCode = 500, $previous = null)
    {
        $this->statusCode = $statusCode;

        if ($previous instanceof RequestException) {
            $this->requestException = $previous;
        }

        // Parse the response body
        $responseData = json_decode($message, true);
        $this->responseData = $responseData ?: ['message' => $message];

        // Extract the error message
        $errorMessage = is_array($responseData) && isset($responseData['message'])
            ? $responseData['message']
            : $message;

        // Extract error type from response headers if available
        if ($previous instanceof RequestException && $previous->getResponse()) {
            $response = $previous->getResponse();

            // Check for x-amzn-ErrorType header
            if ($response->hasHeader('x-amzn-ErrorType')) {
                $this->errorType = $response->getHeaderLine('x-amzn-ErrorType');
            }

            // Check for Retry-After header for rate limit errors
            if ($statusCode === self::STATUS_RATE_LIMITED && $response->hasHeader('Retry-After')) {
                $this->retryAfter = (int)$response->getHeaderLine('Retry-After');
            }
        }

        // Set the error type based on status code if not already set
        if (!$this->errorType) {
            $this->errorType = $this->mapStatusCodeToErrorType($statusCode);
        }

        parent::__construct($errorMessage, $statusCode, $previous);
    }

    /**
     * Map HTTP status code to error type
     */
    protected function mapStatusCodeToErrorType(int $statusCode): string
    {
        return match ($statusCode) {
            self::STATUS_BAD_REQUEST => self::ERROR_TYPE_VALIDATION,
            self::STATUS_UNAUTHORIZED => self::ERROR_TYPE_AUTHORIZATION,
            self::STATUS_NOT_FOUND => self::ERROR_TYPE_RESOURCE_NOT_FOUND,
            self::STATUS_CONFLICT => self::ERROR_TYPE_CONFLICT,
            self::STATUS_RATE_LIMITED => self::ERROR_TYPE_RATE_LIMIT,
            default => self::ERROR_TYPE_SERVER_ERROR,
        };
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the raw response data
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Get the error type
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Get the retry-after value for rate limit errors
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get the original request exception
     */
    public function getRequestException(): ?RequestException
    {
        return $this->requestException;
    }

    /**
     * Check if this is an authentication error
     */
    public function isAuthError(): bool
    {
        return $this->statusCode === self::STATUS_UNAUTHORIZED;
    }

    /**
     * Check if this is a rate limit error
     */
    public function isRateLimitError(): bool
    {
        return $this->statusCode === self::STATUS_RATE_LIMITED;
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->statusCode === self::STATUS_BAD_REQUEST;
    }

    /**
     * Check if this is a resource not found error
     */
    public function isNotFoundError(): bool
    {
        return $this->statusCode === self::STATUS_NOT_FOUND;
    }

    /**
     * Check if this is a conflict error (optimistic locking)
     */
    public function isConflictError(): bool
    {
        return $this->statusCode === self::STATUS_CONFLICT;
    }

    /**
     * Check if this is a server error
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= self::STATUS_SERVER_ERROR;
    }

    /**
     * Get retry suggestions for rate limit errors
     */
    public function getRetrySuggestion(): string
    {
        if (!$this->isRateLimitError()) {
            return 'This error does not support retrying.';
        }

        $seconds = $this->retryAfter ?? 60;
        return "Rate limit exceeded. Retry after {$seconds} seconds with exponential backoff.";
    }
}
