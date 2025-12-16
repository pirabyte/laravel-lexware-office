<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use GuzzleHttp\Exception\RequestException;
use RuntimeException;

class LexwareOfficeApiException extends RuntimeException
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

    private ApiError $error;

    private ?RequestException $requestException = null;

    /**
     * Create a new Lexware Office API exception
     *
     * @param  string  $rawBody  The raw response body (JSON or plain text)
     * @param  int  $statusCode  The HTTP status code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(string $rawBody, int $statusCode = 500, ?\Throwable $previous = null)
    {
        if ($previous instanceof RequestException) {
            $this->requestException = $previous;
        }

        $decodedMessage = $this->extractMessageFromBody($rawBody);
        $errorType = $this->extractErrorType($statusCode, $previous);
        $retryAfter = $this->extractRetryAfterSeconds($statusCode, $previous);

        $this->error = new ApiError(
            message: $decodedMessage,
            type: $errorType,
            rawBody: $rawBody,
            retryAfterSeconds: $retryAfter
        );

        parent::__construct($decodedMessage, $statusCode, $previous);
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
     * Get structured API error details.
     */
    public function getError(): ApiError
    {
        return $this->error;
    }

    public function getStatusCode(): int
    {
        return (int) $this->getCode();
    }

    /**
     * Get the error type
     */
    public function getErrorType(): string
    {
        return $this->error->type;
    }

    /**
     * Get the retry-after value for rate limit errors
     */
    public function getRetryAfter(): ?int
    {
        return $this->error->retryAfterSeconds;
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
        return $this->getCode() === self::STATUS_UNAUTHORIZED;
    }

    /**
     * Check if this is a rate limit error
     */
    public function isRateLimitError(): bool
    {
        return $this->getCode() === self::STATUS_RATE_LIMITED;
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->getCode() === self::STATUS_BAD_REQUEST;
    }

    /**
     * Check if this is a resource not found error
     */
    public function isNotFoundError(): bool
    {
        return $this->getCode() === self::STATUS_NOT_FOUND;
    }

    /**
     * Check if this is a conflict error (optimistic locking)
     */
    public function isConflictError(): bool
    {
        return $this->getCode() === self::STATUS_CONFLICT;
    }

    /**
     * Check if this is a server error
     */
    public function isServerError(): bool
    {
        return $this->getCode() >= self::STATUS_SERVER_ERROR;
    }

    /**
     * Get retry suggestions for rate limit errors
     */
    public function getRetrySuggestion(): string
    {
        if (! $this->isRateLimitError()) {
            return 'This error does not support retrying.';
        }

        $seconds = $this->getRetryAfter() ?? 60;

        return "Rate limit exceeded. Retry after {$seconds} seconds with exponential backoff.";
    }

    private function extractMessageFromBody(string $rawBody): string
    {
        $decoded = json_decode($rawBody, true);
        if (! is_array($decoded)) {
            return $rawBody;
        }

        $message = $decoded['message'] ?? null;
        if (! is_string($message) || $message === '') {
            return $rawBody;
        }

        return $message;
    }

    private function extractErrorType(int $statusCode, ?\Throwable $previous): string
    {
        if ($previous instanceof RequestException && $previous->getResponse() && $previous->getResponse()->hasHeader('x-amzn-ErrorType')) {
            $header = $previous->getResponse()->getHeaderLine('x-amzn-ErrorType');
            if ($header !== '') {
                return $header;
            }
        }

        return $this->mapStatusCodeToErrorType($statusCode);
    }

    private function extractRetryAfterSeconds(int $statusCode, ?\Throwable $previous): ?int
    {
        if ($statusCode !== self::STATUS_RATE_LIMITED) {
            return null;
        }

        if (! ($previous instanceof RequestException) || ! $previous->getResponse() || ! $previous->getResponse()->hasHeader('Retry-After')) {
            return null;
        }

        $value = (int) $previous->getResponse()->getHeaderLine('Retry-After');

        return $value > 0 ? $value : null;
    }
}
