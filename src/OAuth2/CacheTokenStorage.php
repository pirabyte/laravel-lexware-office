<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use Illuminate\Support\Facades\Cache;

class CacheTokenStorage implements RefreshTokenStorage
{
    public function __construct(
        protected string $cacheKey = 'lexware_office_token',
        protected ?string $store = null,
        protected ?int $refreshTokenTtl = null
    ) {}

    public function getRefreshLockKey(): string
    {
        return 'cache:'.($this->store ?? config('cache.default')).':'.$this->cacheKey;
    }

    public function storeToken(#[\SensitiveParameter] LexwareAccessToken $token): void
    {
        $this->withWriteLock(fn () => $this->putToken($token));
    }

    public function storeTokenIfCurrent(
        #[\SensitiveParameter] LexwareAccessToken $token,
        #[\SensitiveParameter] string $refreshToken
    ): bool {
        return $this->withWriteLock(function () use ($token, $refreshToken): bool {
            if ($this->getToken()?->getRefreshToken() !== $refreshToken) {
                return false;
            }

            $this->putToken($token);

            return true;
        });
    }

    public function getToken(): ?LexwareAccessToken
    {
        $tokenData = Cache::store($this->store)->get($this->cacheKey);

        if (! $tokenData) {
            return null;
        }

        try {
            return LexwareAccessToken::fromJson($tokenData);
        } catch (\Throwable) {
            // A corrupt read must not delete credentials replaced by another worker.
            return null;
        }
    }

    public function clearToken(): void
    {
        $this->withWriteLock(fn () => Cache::store($this->store)->forget($this->cacheKey));
    }

    private function putToken(#[\SensitiveParameter] LexwareAccessToken $token): void
    {
        $ttl = $this->refreshTokenTtl ?? (int) config('lexware-office.oauth2.token_storage.refresh_token_ttl', 31536000);
        $ttl = max($ttl, $token->getRemainingTime() + 300, 60);

        if (! Cache::store($this->store)->put($this->cacheKey, $token->toJson(), $ttl)) {
            throw new \RuntimeException('Unable to persist OAuth credentials in cache.');
        }
    }

    private function withWriteLock(callable $callback): mixed
    {
        return Cache::store($this->store)
            ->lock('lexware-token-write:'.hash('sha256', $this->cacheKey), 10)
            ->block(5, $callback);
    }
}
