<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

class LexwareAccessToken implements \JsonSerializable
{
    protected string $accessToken;
    
    protected string $tokenType;
    
    protected int $expiresIn;
    
    protected ?string $refreshToken;
    
    protected array $scopes;
    
    protected \DateTimeInterface $createdAt;

    public function __construct(
        string $accessToken,
        string $tokenType,
        int $expiresIn,
        ?string $refreshToken = null,
        array $scopes = [],
        ?\DateTimeInterface $createdAt = null
    ) {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
        $this->scopes = $scopes;
        $this->createdAt = $createdAt ?: new \DateTime();
    }

    /**
     * Parse scopes from string or array
     */
    private static function parseScopes($scopes): array
    {
        if (is_array($scopes)) {
            return array_filter($scopes, function($scope) {
                return !empty($scope);
            });
        }
        
        if (is_string($scopes) && !empty($scopes)) {
            return explode(' ', $scopes);
        }
        
        return [];
    }

    /**
     * Create from API response array
     */
    public static function fromArray(array $data): self
    {
        $scopes = $data['scope'] ?? $data['scopes'] ?? null;
        
        return new self(
            $data['access_token'],
            $data['token_type'] ?? 'Bearer',
            $data['expires_in'] ?? 3600,
            $data['refresh_token'] ?? null,
            self::parseScopes($scopes),
            new \DateTime()
        );
    }

    /**
     * Get the access token value
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the token type (usually "Bearer")
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Get expires in seconds from creation
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * Get the refresh token if available
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Get granted scopes
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Get token creation time
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Get token expiration time
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return (clone $this->createdAt)->add(new \DateInterval("PT{$this->expiresIn}S"));
    }

    /**
     * Get token expiry time (alias for getExpiresAt)
     */
    public function getExpiryTime(): \DateTimeInterface
    {
        return $this->getExpiresAt();
    }

    /**
     * Check if token is expired (with 30 second buffer)
     */
    public function isExpired(int $bufferSeconds = 30): bool
    {
        $expiresAt = $this->getExpiresAt();
        $now = new \DateTime();
        
        // Add buffer to account for network delays
        $expiresAt->sub(new \DateInterval("PT{$bufferSeconds}S"));
        
        return $now >= $expiresAt;
    }

    /**
     * Check if token will expire within given seconds
     */
    public function isExpiringSoon(int $seconds = 300): bool
    {
        $expiresAt = $this->getExpiresAt();
        $checkTime = (new \DateTime())->add(new \DateInterval("PT{$seconds}S"));
        
        return $checkTime >= $expiresAt;
    }

    /**
     * Get Authorization header value
     */
    public function getAuthorizationHeader(): string
    {
        return $this->tokenType . ' ' . $this->accessToken;
    }

    /**
     * Check if token has specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Check if token has all required scopes
     */
    public function hasScopes(array $scopes): bool
    {
        if (empty($scopes)) {
            return true;
        }
        
        foreach ($scopes as $scope) {
            if (!$this->hasScope($scope)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get remaining time until expiration in seconds
     */
    public function getRemainingTime(): int
    {
        $expiresAt = $this->getExpiresAt();
        $now = new \DateTime();
        
        if ($now >= $expiresAt) {
            return 0;
        }
        
        return $expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Serialize for storage
     */
    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
            'scopes' => $this->scopes,
            'created_at' => $this->createdAt->format(\DateTime::RFC3339),
        ];
    }

    /**
     * Create from stored JSON data
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON for token data');
        }
        
        $scopes = $data['scope'] ?? $data['scopes'] ?? null;
        
        return new self(
            $data['access_token'],
            $data['token_type'] ?? 'Bearer',
            $data['expires_in'] ?? 3600,
            $data['refresh_token'] ?? null,
            self::parseScopes($scopes),
            isset($data['created_at']) ? new \DateTime($data['created_at']) : new \DateTime()
        );
    }

    /**
     * Convert to JSON string for storage
     */
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize());
    }
}