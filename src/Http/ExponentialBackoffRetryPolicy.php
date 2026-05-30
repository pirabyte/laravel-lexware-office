<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\TransportException;

final readonly class ExponentialBackoffRetryPolicy implements RetryPolicy
{
    public function __construct(
        public int $maxAttempts = 3,
        public int $baseDelayMicroseconds = 200_000, // 200ms
        public int $maxDelayMicroseconds = 5_000_000, // 5s
    ) {}

    public function decide(
        HttpMethod $method,
        int $attempt,
        ?LexwareOfficeApiException $apiException,
        ?TransportException $transportException
    ): RetryDecision {
        if ($attempt >= $this->maxAttempts) {
            return RetryDecision::no();
        }

        if ($transportException !== null) {
            if (! $this->isIdempotent($method)) {
                return RetryDecision::no();
            }

            return RetryDecision::yes($this->backoff($attempt));
        }

        if ($apiException === null) {
            return RetryDecision::no();
        }

        $statusCode = $apiException->getStatusCode();

        // 429 is safe to retry for any method (request was rejected).
        if ($statusCode === LexwareOfficeApiException::STATUS_RATE_LIMITED) {
            $retryAfter = $apiException->getRetryAfter();
            if ($retryAfter !== null && $retryAfter > 0) {
                return RetryDecision::yes($retryAfter * 1_000_000);
            }

            return RetryDecision::yes($this->backoff($attempt));
        }

        // Retry only idempotent methods for transient server errors.
        if (! $this->isIdempotent($method)) {
            return RetryDecision::no();
        }

        if (in_array($statusCode, [500, 502, 503, 504], true)) {
            return RetryDecision::yes($this->backoff($attempt));
        }

        return RetryDecision::no();
    }

    private function backoff(int $attempt): int
    {
        $delay = $this->baseDelayMicroseconds * (2 ** max(0, $attempt - 1));

        return min($delay, $this->maxDelayMicroseconds);
    }

    private function isIdempotent(HttpMethod $method): bool
    {
        return in_array($method, [HttpMethod::GET, HttpMethod::PUT, HttpMethod::DELETE], true);
    }
}


