<?php

namespace Pirabyte\LaravelLexwareOffice\RateLimiting;

use InvalidArgumentException;

final readonly class RateLimitBucket
{
    public function __construct(
        public string $scope,
        public string $identifier,
        public int $requestsPerSecond,
        public int $burstSize,
        public bool $perEndpoint = false,
    ) {
        if ($scope === '' || $identifier === '' || $requestsPerSecond < 1 || $burstSize < 1) {
            throw new InvalidArgumentException('Rate limit buckets require an identity, a positive rate, and a positive burst size.');
        }
    }

    public function cacheKey(string $method, string $endpoint): string
    {
        $group = 'all';

        if ($this->perEndpoint) {
            $path = parse_url($endpoint, PHP_URL_PATH) ?: $endpoint;
            $path = preg_replace(
                '~/(?:[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}|\d+)(?=/|$)~i',
                '/{id}',
                $path,
            ) ?? $path;
            $group = strtoupper($method).' '.trim($path, '/');
        }

        return 'lexware-office:rate-limit:'.hash('sha256', $this->scope."\0".$this->identifier."\0".$group);
    }
}
