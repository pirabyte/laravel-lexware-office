<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

interface LexwareTokenStorage
{
    /**
     * Store an access token
     */
    public function storeToken(LexwareAccessToken $token): void;

    /**
     * Retrieve the stored access token
     */
    public function getToken(): ?LexwareAccessToken;

    /**
     * Clear the stored token
     */
    public function clearToken(): void;
}
