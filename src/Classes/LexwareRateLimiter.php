<?php

namespace Pirabyte\LaravelLexwareOffice\Classes;

use Illuminate\Support\Facades\Cache;

class LexwareRateLimiter
{
    /**
     * Connection/Endpoint rate limit: 2 requests per second with burst size 5
     */
    const CONNECTION_RATE_LIMIT = 2;
    const CONNECTION_BURST_SIZE = 5;
    
    /**
     * Client/Endpoint rate limit: 5 requests per second with burst size 5
     */
    const CLIENT_RATE_LIMIT = 5;
    const CLIENT_BURST_SIZE = 5;
    
    /**
     * Window size in seconds
     */
    const WINDOW_SIZE = 1;

    protected string $cachePrefix;
    protected string $connectionId;
    protected string $clientId;

    public function __construct(string $connectionId, string $clientId, string $cachePrefix = 'lexware_rate_limit')
    {
        $this->connectionId = $connectionId;
        $this->clientId = $clientId;
        $this->cachePrefix = $cachePrefix;
    }

    /**
     * Check if request is allowed for both connection and client rate limits
     *
     * @param string $endpoint The API endpoint being accessed
     * @return array ['allowed' => bool, 'waitTime' => int, 'limitType' => string|null]
     */
    public function isAllowed(string $endpoint): array
    {
        // Check connection rate limit first
        $connectionResult = $this->checkRateLimit(
            $this->getConnectionKey($endpoint),
            self::CONNECTION_RATE_LIMIT,
            self::CONNECTION_BURST_SIZE
        );
        
        if (!$connectionResult['allowed']) {
            return [
                'allowed' => false,
                'waitTime' => $connectionResult['waitTime'],
                'limitType' => 'connection'
            ];
        }

        // Check client rate limit
        $clientResult = $this->checkRateLimit(
            $this->getClientKey($endpoint),
            self::CLIENT_RATE_LIMIT,
            self::CLIENT_BURST_SIZE
        );
        
        if (!$clientResult['allowed']) {
            return [
                'allowed' => false,
                'waitTime' => $clientResult['waitTime'],
                'limitType' => 'client'
            ];
        }

        return [
            'allowed' => true,
            'waitTime' => 0,
            'limitType' => null
        ];
    }

    /**
     * Record a successful request for both connection and client limits
     *
     * @param string $endpoint The API endpoint being accessed
     */
    public function recordHit(string $endpoint): void
    {
        $this->recordRateLimitHit(
            $this->getConnectionKey($endpoint),
            self::CONNECTION_RATE_LIMIT,
            self::CONNECTION_BURST_SIZE
        );
        
        $this->recordRateLimitHit(
            $this->getClientKey($endpoint),
            self::CLIENT_RATE_LIMIT,
            self::CLIENT_BURST_SIZE
        );
    }

    /**
     * Generate connection-specific rate limit key
     *
     * @param string $endpoint
     * @return string
     */
    protected function getConnectionKey(string $endpoint): string
    {
        return $this->cachePrefix . ':connection:' . $this->connectionId . ':' . $this->normalizeEndpoint($endpoint);
    }

    /**
     * Generate client-specific rate limit key
     *
     * @param string $endpoint
     * @return string
     */
    protected function getClientKey(string $endpoint): string
    {
        return $this->cachePrefix . ':client:' . $this->clientId . ':' . $this->normalizeEndpoint($endpoint);
    }

    /**
     * Normalize endpoint for consistent key generation
     *
     * @param string $endpoint
     * @return string
     */
    protected function normalizeEndpoint(string $endpoint): string
    {
        // Extract the main endpoint part (remove query parameters and IDs)
        $normalized = preg_replace('/\/[0-9a-f-]{36,}/', '/{id}', $endpoint); // Replace UUIDs
        $normalized = preg_replace('/\/\d+/', '/{id}', $normalized); // Replace numeric IDs
        $normalized = preg_replace('/\?.*$/', '', $normalized); // Remove query parameters
        $normalized = trim($normalized, '/');
        
        return str_replace('/', '_', $normalized);
    }

    /**
     * Check rate limit using token bucket algorithm
     *
     * @param string $key Cache key
     * @param int $rateLimit Requests per second
     * @param int $burstSize Maximum burst size
     * @return array ['allowed' => bool, 'waitTime' => int]
     */
    protected function checkRateLimit(string $key, int $rateLimit, int $burstSize): array
    {
        $now = time();
        $bucket = Cache::get($key, [
            'tokens' => $burstSize,
            'lastRefill' => $now
        ]);

        // Calculate tokens to add based on time elapsed
        $timePassed = $now - $bucket['lastRefill'];
        $tokensToAdd = $timePassed * $rateLimit;
        
        // Refill bucket (max is burst size)
        $bucket['tokens'] = min($burstSize, $bucket['tokens'] + $tokensToAdd);
        $bucket['lastRefill'] = $now;

        // Check if we have tokens available
        if ($bucket['tokens'] >= 1) {
            return ['allowed' => true, 'waitTime' => 0];
        }

        // Calculate wait time until next token is available
        $waitTime = ceil((1 - $bucket['tokens']) / $rateLimit);
        
        return ['allowed' => false, 'waitTime' => $waitTime];
    }

    /**
     * Record a rate limit hit (consume a token)
     *
     * @param string $key Cache key
     * @param int $rateLimit Requests per second
     * @param int $burstSize Maximum burst size
     */
    protected function recordRateLimitHit(string $key, int $rateLimit, int $burstSize): void
    {
        $now = time();
        $bucket = Cache::get($key, [
            'tokens' => $burstSize,
            'lastRefill' => $now
        ]);

        // Calculate tokens to add based on time elapsed
        $timePassed = $now - $bucket['lastRefill'];
        $tokensToAdd = $timePassed * $rateLimit;
        
        // Refill bucket (max is burst size)
        $bucket['tokens'] = min($burstSize, $bucket['tokens'] + $tokensToAdd);
        $bucket['lastRefill'] = $now;

        // Consume one token
        $bucket['tokens'] = max(0, $bucket['tokens'] - 1);

        // Store the updated bucket state
        Cache::put($key, $bucket, self::WINDOW_SIZE * 60); // Cache for 60 seconds (longer than needed for safety)
    }

    /**
     * Get rate limit status for debugging
     *
     * @param string $endpoint
     * @return array
     */
    public function getStatus(string $endpoint): array
    {
        $connectionKey = $this->getConnectionKey($endpoint);
        $clientKey = $this->getClientKey($endpoint);
        
        $connectionBucket = Cache::get($connectionKey, [
            'tokens' => self::CONNECTION_BURST_SIZE,
            'lastRefill' => time()
        ]);
        
        $clientBucket = Cache::get($clientKey, [
            'tokens' => self::CLIENT_BURST_SIZE,
            'lastRefill' => time()
        ]);

        return [
            'connection' => [
                'tokens' => $connectionBucket['tokens'],
                'limit' => self::CONNECTION_RATE_LIMIT,
                'burst' => self::CONNECTION_BURST_SIZE,
                'key' => $connectionKey
            ],
            'client' => [
                'tokens' => $clientBucket['tokens'],
                'limit' => self::CLIENT_RATE_LIMIT,
                'burst' => self::CLIENT_BURST_SIZE,
                'key' => $clientKey
            ]
        ];
    }
}