<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\OAuthErrorCategory;

class LexwareTokenPersistenceException extends LexwareOAuthException
{
    /** @var \WeakMap<self, LexwareAccessToken>|null */
    private static ?\WeakMap $recoveryTokens = null;

    public function __construct(#[\SensitiveParameter] LexwareAccessToken $token)
    {
        parent::__construct(
            'Token rotation succeeded but local persistence failed. Recover the received token pair before another refresh.',
            OAuthErrorCategory::OutcomeUnknown,
            200
        );

        // Keep credentials outside exception properties, serialization and log context.
        self::$recoveryTokens ??= new \WeakMap;
        self::$recoveryTokens[$this] = $token;
    }

    public function takeToken(): ?LexwareAccessToken
    {
        $token = self::$recoveryTokens[$this] ?? null;
        unset(self::$recoveryTokens[$this]);

        return $token;
    }
}
