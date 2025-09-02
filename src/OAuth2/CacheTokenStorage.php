<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use Illuminate\Support\Facades\Cache;

class CacheTokenStorage implements LexwareTokenStorage
{
    protected string $cacheKey;

    public function __construct(string $cacheKey = 'lexware_office_token')
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * Store an access token in cache
     */
    public function storeToken(LexwareAccessToken $token): void
    {
        // Store until the token expires plus some buffer
        $ttl = max($token->getRemainingTime() + 300, 60); // At least 1 minute

        Cache::put($this->cacheKey, $token->toJson(), $ttl);
    }

    /**
     * Retrieve the stored access token from cache
     */
    public function getToken(): ?LexwareAccessToken
    {
        $tokenData = Cache::get($this->cacheKey);

        if (! $tokenData) {
            return null;
        }

        try {
            return LexwareAccessToken::fromJson($tokenData);
        } catch (\Exception $e) {
            // If token data is corrupted, clear it
            $this->clearToken();

            return null;
        }
    }

    /**
     * Clear the stored token from cache
     */
    public function clearToken(): void
    {
        Cache::forget($this->cacheKey);
    }
}
