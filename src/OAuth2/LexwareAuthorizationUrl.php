<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

class LexwareAuthorizationUrl
{
    protected string $url;
    
    protected string $state;
    
    protected string $codeVerifier;

    public function __construct(string $url, string $state, string $codeVerifier)
    {
        $this->url = $url;
        $this->state = $state;
        $this->codeVerifier = $codeVerifier;
    }

    /**
     * Get the authorization URL to redirect users to
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the state parameter for verification
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get the PKCE code verifier (internal use)
     */
    public function getCodeVerifier(): string
    {
        return $this->codeVerifier;
    }

    /**
     * Convert to string (returns URL)
     */
    public function __toString(): string
    {
        return $this->url;
    }
}