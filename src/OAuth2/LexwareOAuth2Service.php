<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

class LexwareOAuth2Service
{
    protected Client $httpClient;
    
    protected string $clientId;
    
    protected string $clientSecret;
    
    protected string $redirectUri;
    
    protected string $baseUrl;
    
    protected array $scopes;
    
    protected ?LexwareTokenStorage $tokenStorage = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        string $baseUrl = 'https://api.lexoffice.io',
        array $scopes = []
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->scopes = $scopes;
        
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
    }

    /**
     * Set custom token storage implementation
     */
    public function setTokenStorage(LexwareTokenStorage $storage): self
    {
        $this->tokenStorage = $storage;
        return $this;
    }

    /**
     * Get the token storage instance
     */
    protected function getTokenStorage(): LexwareTokenStorage
    {
        if (!$this->tokenStorage) {
            $this->tokenStorage = new CacheTokenStorage();
        }
        
        return $this->tokenStorage;
    }

    /**
     * Generate authorization URL with PKCE
     * 
     * @param string|null $state Optional state parameter for additional security
     * @return LexwareAuthorizationUrl
     */
    public function getAuthorizationUrl(?string $state = null): LexwareAuthorizationUrl
    {
        $state = $state ?: Str::random(32);
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        
        // Store PKCE data for later verification
        $this->storePkceData($state, $codeVerifier);
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];
        
        $url = $this->baseUrl . '/oauth2/authorize?' . http_build_query($params);
        
        return new LexwareAuthorizationUrl($url, $state, $codeVerifier);
    }

    /**
     * Exchange authorization code for access token
     * 
     * @param string $code Authorization code from callback
     * @param string $state State parameter from callback
     * @return LexwareAccessToken
     * @throws LexwareOfficeApiException
     */
    public function exchangeCodeForToken(string $code, string $state): LexwareAccessToken
    {
        $codeVerifier = $this->retrievePkceData($state);
        
        if (!$codeVerifier) {
            throw new LexwareOfficeApiException('Invalid or expired state parameter', 400);
        }
        
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $codeVerifier,
        ];
        
        try {
            $response = $this->httpClient->post($this->baseUrl . '/oauth2/token', [
                'form_params' => $params,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LexwareOfficeApiException('Invalid JSON response from token endpoint', 500);
            }
            
            $accessToken = LexwareAccessToken::fromArray($data);
            
            // Store the token
            $this->getTokenStorage()->storeToken($accessToken);
            
            // Clean up PKCE data
            $this->cleanupPkceData($state);
            
            return $accessToken;
            
        } catch (GuzzleException $e) {
            throw new LexwareOfficeApiException(
                'Failed to exchange authorization code: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                $e
            );
        }
    }

    /**
     * Refresh an access token using refresh token
     * 
     * @param string|null $refreshToken Optional specific refresh token, otherwise uses stored token
     * @return LexwareAccessToken
     * @throws LexwareOfficeApiException
     */
    public function refreshToken(?string $refreshToken = null): LexwareAccessToken
    {
        if (!$refreshToken) {
            $currentToken = $this->getTokenStorage()->getToken();
            if (!$currentToken || !$currentToken->getRefreshToken()) {
                throw new LexwareOfficeApiException('No refresh token available', 400);
            }
            $refreshToken = $currentToken->getRefreshToken();
        }
        
        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ];
        
        try {
            $response = $this->httpClient->post($this->baseUrl . '/oauth2/token', [
                'form_params' => $params,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LexwareOfficeApiException('Invalid JSON response from token refresh', 500);
            }
            
            $accessToken = LexwareAccessToken::fromArray($data);
            
            // Store the new token
            $this->getTokenStorage()->storeToken($accessToken);
            
            return $accessToken;
            
        } catch (GuzzleException $e) {
            throw new LexwareOfficeApiException(
                'Failed to refresh token: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                $e
            );
        }
    }

    /**
     * Get current valid access token (refreshes automatically if needed)
     * 
     * @return LexwareAccessToken|null
     * @throws LexwareOfficeApiException
     */
    public function getValidAccessToken(): ?LexwareAccessToken
    {
        $token = $this->getTokenStorage()->getToken();
        
        if (!$token) {
            return null;
        }
        
        // If token is not expired, return it
        if (!$token->isExpired()) {
            return $token;
        }
        
        // If token is expired but we have a refresh token, refresh it
        if ($token->getRefreshToken()) {
            return $this->refreshToken($token->getRefreshToken());
        }
        
        // Token is expired and no refresh token available
        return null;
    }

    /**
     * Revoke token (logout)
     * 
     * @param string|null $token Token to revoke, defaults to current access token
     * @return bool
     */
    public function revokeToken(?string $token = null): bool
    {
        if (!$token) {
            $currentToken = $this->getTokenStorage()->getToken();
            if (!$currentToken) {
                return true; // No token to revoke
            }
            $token = $currentToken->getAccessToken();
        }
        
        try {
            $this->httpClient->post($this->baseUrl . '/oauth2/revoke', [
                'form_params' => [
                    'token' => $token,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);
            
            // Clear stored token
            $this->getTokenStorage()->clearToken();
            
            return true;
            
        } catch (GuzzleException $e) {
            // Even if revocation fails, clear the local token
            $this->getTokenStorage()->clearToken();
            return false;
        }
    }

    /**
     * Generate PKCE code verifier
     */
    protected function generateCodeVerifier(): string
    {
        return Str::random(128);
    }

    /**
     * Generate PKCE code challenge from verifier
     */
    protected function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Store PKCE data temporarily
     */
    protected function storePkceData(string $state, string $codeVerifier): void
    {
        Cache::put("lexware_pkce_{$state}", $codeVerifier, now()->addMinutes(10));
    }

    /**
     * Retrieve and validate PKCE data
     */
    protected function retrievePkceData(string $state): ?string
    {
        return Cache::pull("lexware_pkce_{$state}");
    }

    /**
     * Clean up PKCE data
     */
    protected function cleanupPkceData(string $state): void
    {
        Cache::forget("lexware_pkce_{$state}");
    }
}