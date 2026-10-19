<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

/** Optional atomic storage capability for rotating refresh tokens. */
interface RefreshTokenStorage extends LexwareTokenStorage
{
    /** Stable identity of the stored connection, independent of its credentials. */
    public function getRefreshLockKey(): string;

    /** Atomically replace only the pair whose refresh token is still current. */
    public function storeTokenIfCurrent(
        #[\SensitiveParameter] LexwareAccessToken $token,
        #[\SensitiveParameter] string $refreshToken
    ): bool;
}
