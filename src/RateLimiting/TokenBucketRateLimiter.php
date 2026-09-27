<?php

namespace Pirabyte\LaravelLexwareOffice\RateLimiting;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use RuntimeException;
use UnexpectedValueException;

final class TokenBucketRateLimiter
{
    /** @var list<RateLimitBucket> */
    private array $buckets;

    public function __construct(RateLimitBucket $bucket, RateLimitBucket ...$additionalBuckets)
    {
        $this->buckets = [$bucket, ...$additionalBuckets];
    }

    /** Reserve one slot in every applicable bucket before sending an HTTP request. */
    public function reserve(string $method, string $endpoint): void
    {
        $bucketsByKey = [];
        foreach ($this->buckets as $bucket) {
            $key = $bucket->cacheKey($method, $endpoint);
            if (isset($bucketsByKey[$key])) {
                throw new InvalidArgumentException('Duplicate Lexware rate limit bucket.');
            }

            $bucketsByKey[$key] = $bucket;
        }

        ksort($bucketsByKey);
        $locks = [];

        try {
            foreach (array_keys($bucketsByKey) as $key) {
                $lock = Cache::lock($key.':lock', count($bucketsByKey) * 5 + 10);
                $lock->block(5);
                $locks[] = $lock;
            }

            $now = microtime(true);
            $available = [];
            $retryAfter = 0;

            foreach ($bucketsByKey as $key => $bucket) {
                $state = Cache::get($key);
                if ($state !== null && (! is_array($state)
                    || ! isset($state['tokens'], $state['updatedAt'])
                    || ! is_numeric($state['tokens'])
                    || ! is_numeric($state['updatedAt']))) {
                    throw new UnexpectedValueException('Invalid Lexware rate limit cache state.');
                }

                $tokens = $state === null
                    ? $bucket->burstSize
                    : min(
                        $bucket->burstSize,
                        (float) $state['tokens'] + max(0, $now - (float) $state['updatedAt']) * $bucket->requestsPerSecond,
                    );

                $available[$key] = $tokens;
                if ($tokens < 1) {
                    $retryAfter = max($retryAfter, (int) ceil((1 - $tokens) / $bucket->requestsPerSecond));
                }
            }

            if ($retryAfter > 0) {
                throw $this->rateLimitException($retryAfter);
            }

            foreach ($bucketsByKey as $key => $bucket) {
                $ttl = max(60, (int) ceil($bucket->burstSize / $bucket->requestsPerSecond) + 1);
                if (! Cache::put($key, ['tokens' => $available[$key] - 1, 'updatedAt' => $now], $ttl)) {
                    throw new RuntimeException('Could not reserve Lexware rate limit capacity.');
                }
            }
        } catch (LockTimeoutException $exception) {
            throw $this->rateLimitException(1, $exception);
        } finally {
            foreach (array_reverse($locks) as $lock) {
                $lock->release();
            }
        }
    }

    private function rateLimitException(int $retryAfter, ?\Throwable $previous = null): LexwareOfficeApiException
    {
        return new LexwareOfficeApiException(json_encode([
            'message' => 'Rate limit exceeded',
            'retryAfter' => $retryAfter,
        ]), LexwareOfficeApiException::STATUS_RATE_LIMITED, $previous);
    }
}
