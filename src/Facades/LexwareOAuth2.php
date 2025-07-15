<?php

namespace Pirabyte\LaravelLexwareOffice\Facades;

use Illuminate\Support\Facades\Facade;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAuthorizationUrl;

/**
 * @method static LexwareAuthorizationUrl getAuthorizationUrl(?string $state = null)
 * @method static LexwareAccessToken exchangeCodeForToken(string $code, string $state)
 * @method static LexwareAccessToken refreshToken(?string $refreshToken = null)
 * @method static LexwareAccessToken|null getValidAccessToken()
 * @method static bool revokeToken(?string $token = null)
 * @method static \Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service setTokenStorage(\Pirabyte\LaravelLexwareOffice\OAuth2\LexwareTokenStorage $storage)
 */
class LexwareOAuth2 extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'lexware-oauth2';
    }
}
