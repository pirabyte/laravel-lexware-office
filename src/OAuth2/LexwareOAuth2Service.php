<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOAuthException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareTokenPersistenceException;
use Psr\Http\Message\ResponseInterface;

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
        #[\SensitiveParameter] string $clientId,
        #[\SensitiveParameter] string $clientSecret,
        string $redirectUri,
        string $baseUrl = 'https://api.lexoffice.io',
        array $scopes = [],
        protected ?string $connectionKey = null
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
    public function getTokenStorage(): LexwareTokenStorage
    {
        if (! $this->tokenStorage) {
            $this->tokenStorage = new CacheTokenStorage;
        }

        return $this->tokenStorage;
    }

    /**
     * Generate authorization URL with PKCE
     *
     * @param  string|null  $state  Optional state parameter for additional security
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

        $url = $this->baseUrl.'/oauth2/authorize?'.http_build_query($params);

        return new LexwareAuthorizationUrl($url, $state, $codeVerifier);
    }

    /**
     * Exchange authorization code for access token
     *
     * @param  string  $code  Authorization code from callback
     * @param  string  $state  State parameter from callback
     *
     * @throws LexwareOfficeApiException
     */
    public function exchangeCodeForToken(#[\SensitiveParameter] string $code, string $state): LexwareAccessToken
    {
        $codeVerifier = $this->retrievePkceData($state);

        if (! $codeVerifier) {
            throw new LexwareOfficeApiException('Invalid or expired state parameter', 400);
        }

        $accessToken = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $codeVerifier,
        ], 'Failed to exchange authorization code');

        $this->persistToken($accessToken);
        $this->cleanupPkceData($state);

        return $accessToken;
    }

    /**
     * Force a refresh, reusing a newer pair if another worker refreshed while waiting.
     *
     * @param  string|null  $refreshToken  Deprecated and ignored. Stored credentials are always authoritative.
     */
    public function refreshToken(#[\SensitiveParameter] ?string $refreshToken = null): LexwareAccessToken
    {
        $token = $this->refreshCurrentToken(0, true);

        if (! $token) {
            throw new LexwareOAuthException('No refresh token available', OAuthErrorCategory::ClientConfiguration);
        }

        return $token;
    }

    public function getValidAccessToken(?int $minimumRemainingSeconds = null): ?LexwareAccessToken
    {
        $minimumRemainingSeconds ??= (int) config('lexware-office.oauth2.refresh.minimum_remaining_seconds', 30);
        if ($minimumRemainingSeconds < 0) {
            throw new \InvalidArgumentException('Minimum remaining lifetime must not be negative.');
        }

        return $this->refreshCurrentToken($minimumRemainingSeconds);
    }

    /** Recover from an API 401 without rotating a pair another worker already replaced. */
    public function refreshAfterUnauthorized(#[\SensitiveParameter] string $rejectedAccessToken): ?LexwareAccessToken
    {
        return $this->refreshCurrentToken(0, true, $rejectedAccessToken);
    }

    public function getRefreshLockKey(): string
    {
        $storage = $this->getTokenStorage();
        $identity = $this->connectionKey ?? ($storage instanceof RefreshTokenStorage ? $storage->getRefreshLockKey() : 'default');

        return 'lexware-oauth-refresh:'.hash('sha256', json_encode([
            config('app.env'), $this->baseUrl, $this->clientId, $identity,
        ], JSON_THROW_ON_ERROR));
    }

    private function refreshCurrentToken(
        int $minimumRemainingSeconds,
        bool $force = false,
        #[\SensitiveParameter] ?string $rejectedAccessToken = null
    ): ?LexwareAccessToken {
        $observed = $this->readToken();
        if (! $force && (! $observed || ! $observed->isExpiringSoon($minimumRemainingSeconds))) {
            return $observed;
        }

        $ttl = (int) config('lexware-office.oauth2.refresh.lock_ttl', 90);
        $wait = (int) config('lexware-office.oauth2.refresh.lock_wait', 10);
        $timeout = (int) config('lexware-office.oauth2.refresh.http_timeout', 20);
        if ($timeout < 1 || $ttl < $timeout + 10 || $wait < 0) {
            throw new LexwareOAuthException('Invalid OAuth refresh lock or timeout configuration.', OAuthErrorCategory::ClientConfiguration);
        }

        $lock = Cache::store(config('lexware-office.oauth2.refresh.lock_store'))->lock($this->getRefreshLockKey(), $ttl);

        try {
            return $lock->block($wait, function () use ($observed, $force, $minimumRemainingSeconds, $rejectedAccessToken): ?LexwareAccessToken {
                $current = $this->readToken();
                if (! $current) {
                    return null;
                }

                $replaced = $observed && $current->getRefreshToken() !== $observed->getRefreshToken();
                $rejectedTokenReplaced = $rejectedAccessToken !== null && $current->getAccessToken() !== $rejectedAccessToken;
                if (($replaced || $rejectedTokenReplaced) && ! $current->isExpired(0)) {
                    return $current;
                }
                if (! $force && ! $current->isExpiringSoon($minimumRemainingSeconds)) {
                    return $current;
                }
                if (! $current->getRefreshToken()) {
                    return ! $force && ! $current->isExpired(0) ? $current : null;
                }

                try {
                    $token = $this->requestToken([
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $current->getRefreshToken(),
                    ], 'Failed to refresh token');
                } catch (LexwareOAuthException $e) {
                    if ($e->category === OAuthErrorCategory::InvalidGrant) {
                        $latest = $this->readToken();
                        if ($latest?->getRefreshToken() !== $current->getRefreshToken()) {
                            if ($latest && ! $latest->isExpired(0)) {
                                return $latest;
                            }

                            throw new LexwareOAuthException('OAuth credentials changed during refresh.', OAuthErrorCategory::OutcomeUnknown);
                        }
                    }

                    throw $e;
                }

                $this->persistToken($token, $current->getRefreshToken());

                return $token;
            });
        } catch (LockTimeoutException) {
            // block() releases only after acquisition. A timed-out waiter must never release the holder.
            throw new LexwareOAuthException('Timed out waiting for OAuth refresh lock.', OAuthErrorCategory::Transient);
        }
    }

    private function readToken(): ?LexwareAccessToken
    {
        try {
            return $this->getTokenStorage()->getToken();
        } catch (\Throwable) {
            throw new LexwareOAuthException('Unable to read stored OAuth credentials.', OAuthErrorCategory::ClientConfiguration);
        }
    }

    private function persistToken(
        #[\SensitiveParameter] LexwareAccessToken $token,
        #[\SensitiveParameter] ?string $previousRefreshToken = null
    ): void {
        $storage = $this->getTokenStorage();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                if ($previousRefreshToken === null) {
                    $storage->storeToken($token);

                    return;
                }

                // An acknowledged write may have succeeded before the storage transport failed.
                $current = $storage->getToken();
                if ($current?->getRefreshToken() === $token->getRefreshToken()
                    && $current->getAccessToken() === $token->getAccessToken()) {
                    return;
                }

                if ($storage instanceof RefreshTokenStorage) {
                    $stored = $storage->storeTokenIfCurrent($token, $previousRefreshToken);
                } else {
                    // Legacy implementations are protected by the refresh lock, but cannot offer atomic CAS.
                    $stored = $current?->getRefreshToken() === $previousRefreshToken;
                    if ($stored) {
                        $storage->storeToken($token);
                    }
                }

            } catch (\Throwable) {
                // Retry only the local write with the response already received, never the rotating grant.
                continue;
            }

            if (! $stored) {
                throw new LexwareOAuthException('Stored OAuth credentials changed during refresh; refusing a stale write.', OAuthErrorCategory::OutcomeUnknown, 200);
            }

            return;
        }

        throw new LexwareTokenPersistenceException($token);
    }

    private function requestToken(#[\SensitiveParameter] array $params, string $failureMessage): LexwareAccessToken
    {
        try {
            $response = $this->httpClient->post($this->baseUrl.'/oauth2/token', [
                'auth' => [$this->clientId, $this->clientSecret, 'basic'],
                'form_params' => $params,
                'http_errors' => false,
                'allow_redirects' => false,
                'timeout' => (int) config('lexware-office.oauth2.refresh.http_timeout', 20),
                'connect_timeout' => 5,
            ]);
        } catch (GuzzleException $e) {
            if ($e instanceof RequestException && $e->getResponse()) {
                throw $this->tokenResponseException($e->getResponse(), $failureMessage);
            }

            $context = $e instanceof ConnectException || $e instanceof RequestException ? $e->getHandlerContext() : [];
            // Only DNS resolution and connection refusal prove the request did not reach the server.
            $category = in_array($context['errno'] ?? null, [6, 7], true)
                ? OAuthErrorCategory::Transient : OAuthErrorCategory::OutcomeUnknown;

            throw new LexwareOAuthException($failureMessage.': transport failure.', $category);
        }

        if ($response->getStatusCode() !== 200) {
            throw $this->tokenResponseException($response, $failureMessage);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (! is_array($data)
            || ! is_string($data['access_token'] ?? null) || $data['access_token'] === ''
            || preg_match('/[\x00-\x20\x7f]/', $data['access_token'])
            || ! is_string($data['refresh_token'] ?? null) || $data['refresh_token'] === ''
            || preg_match('/[\x00-\x20\x7f]/', $data['refresh_token'])
            || ! is_int($data['expires_in'] ?? null) || $data['expires_in'] <= 0
            || (array_key_exists('token_type', $data) && (! is_string($data['token_type']) || strcasecmp($data['token_type'], 'Bearer') !== 0))
            || (array_key_exists('scope', $data) && ! is_string($data['scope']))
        ) {
            throw new LexwareOAuthException($failureMessage.': invalid token response.', OAuthErrorCategory::ClientConfiguration, 200);
        }

        try {
            $token = LexwareAccessToken::fromArray($data);
            $token->getExpiresAt();

            return $token;
        } catch (\Throwable) {
            throw new LexwareOAuthException($failureMessage.': invalid token lifetime.', OAuthErrorCategory::ClientConfiguration, 200);
        }
    }

    private function tokenResponseException(#[\SensitiveParameter] ResponseInterface $response, string $message): LexwareOAuthException
    {
        $status = $response->getStatusCode();
        $data = json_decode((string) $response->getBody(), true);
        $descriptions = [
            'invalid_grant' => 'The current grant is invalid or expired.',
            'invalid_client' => 'Client authentication failed.',
            'unauthorized_client' => 'The OAuth client is not authorized.',
            'invalid_request' => 'The OAuth request is malformed.',
            'unsupported_grant_type' => 'The grant type is not supported.',
            'invalid_scope' => 'The requested scope is invalid.',
        ];
        $error = is_array($data) && is_string($data['error'] ?? null) && isset($descriptions[$data['error']]) ? $data['error'] : null;
        $category = match (true) {
            $status === 429 || $status >= 500 => OAuthErrorCategory::Transient,
            $error === 'invalid_grant' => OAuthErrorCategory::InvalidGrant,
            default => OAuthErrorCategory::ClientConfiguration,
        };

        $retryAfter = $response->getHeaderLine('Retry-After');
        $retrySeconds = ctype_digit($retryAfter) ? min((int) $retryAfter, PHP_INT_MAX) : null;
        if ($retrySeconds === null && $retryAfter !== '') {
            $date = strtotime($retryAfter);
            $retrySeconds = $date === false ? null : max(0, $date - time());
        }

        // Server prose can echo arbitrary credentials. Expose only bounded canonical descriptions.
        $description = isset($data['error_description']) && is_string($data['error_description'])
            ? ($descriptions[$error] ?? 'The token endpoint returned an unrecognized error.') : null;

        return new LexwareOAuthException($message.'.', $category, $status, $error, $description, $retrySeconds);
    }

    /**
     * Revoke token (logout)
     *
     * @param  string|null  $token  Token to revoke, defaults to current access token
     */
    public function revokeToken(?string $token = null): bool
    {
        if (! $token) {
            $currentToken = $this->getTokenStorage()->getToken();
            if (! $currentToken) {
                return true; // No token to revoke
            }
            $token = $currentToken->getAccessToken();
        }

        try {
            $this->httpClient->post($this->baseUrl.'/oauth2/revoke', [
                'auth' => [$this->clientId, $this->clientSecret, 'basic'],
                'form_params' => ['token' => $token],
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
