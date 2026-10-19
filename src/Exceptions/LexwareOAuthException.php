<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Pirabyte\LaravelLexwareOffice\OAuth2\OAuthErrorCategory;

class LexwareOAuthException extends LexwareOfficeApiException
{
    public function __construct(
        string $message,
        public readonly OAuthErrorCategory $category,
        int $statusCode = 0,
        public readonly ?string $oauthError = null,
        public readonly ?string $oauthErrorDescription = null,
        ?int $retryAfter = null
    ) {
        // Never retain a transport exception: it can contain credentials and request bodies.
        parent::__construct($message, $statusCode);
        $this->retryAfter = $retryAfter;
    }

    public function __serialize(): array
    {
        return ['message' => $this->getMessage()] + $this->context();
    }

    public function __unserialize(array $data): void
    {
        $this->category = OAuthErrorCategory::from($data['category']);
        $this->oauthError = $data['oauthError'];
        $this->oauthErrorDescription = $data['oauthErrorDescription'];
        parent::__construct($data['message'], $data['httpStatus']);
        $this->retryAfter = $data['retryAfter'];
    }

    public function __debugInfo(): array
    {
        return $this->__serialize();
    }

    public function context(): array
    {
        return [
            'httpStatus' => $this->getStatusCode(),
            'category' => $this->category->value,
            'oauthError' => $this->oauthError,
            'oauthErrorDescription' => $this->oauthErrorDescription,
            'retryAfter' => $this->getRetryAfter(),
        ];
    }
}
