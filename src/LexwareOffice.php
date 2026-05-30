<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice;

use GuzzleHttp\ClientInterface;
use Pirabyte\LaravelLexwareOffice\Http\GuzzleTransport;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAuthorizationUrl;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\CountryResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialAccountResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialTransactionResource;
use Pirabyte\LaravelLexwareOffice\Resources\PartnerIntegrationResource;
use Pirabyte\LaravelLexwareOffice\Resources\PostingCategoryResource;
use Pirabyte\LaravelLexwareOffice\Resources\ProfileResource;
use Pirabyte\LaravelLexwareOffice\Resources\TransactionAssignmentHintResource;
use Pirabyte\LaravelLexwareOffice\Resources\VoucherResource;

/**
 * v2 Lexware Office client (DTO-first, no arrays in public API).
 */
class LexwareOffice
{
    private LexwareHttpClient $http;

    private ContactResource $contacts;
    private VoucherResource $vouchers;
    private ProfileResource $profile;
    private PostingCategoryResource $postingCategories;
    private CountryResource $countries;
    private FinancialAccountResource $financialAccounts;
    private FinancialTransactionResource $financialTransactions;
    private TransactionAssignmentHintResource $transactionAssignmentHints;
    private PartnerIntegrationResource $partnerIntegrations;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        string $rateLimitKey = 'lexware_office_api',
        int $maxRequestsPerMinute = 50,
        float $timeoutSeconds = 30.0,
        ?ClientInterface $httpClient = null,
    ) {
        $this->http = new LexwareHttpClient(
            baseUrl: $this->baseUrl,
            apiKey: $this->apiKey,
            rateLimitKey: $rateLimitKey,
            maxRequestsPerMinute: $maxRequestsPerMinute,
            timeoutSeconds: $timeoutSeconds,
            transport: $httpClient ? new GuzzleTransport($httpClient) : null,
        );

        $this->contacts = new ContactResource($this->http);
        $this->vouchers = new VoucherResource($this->http);
        $this->profile = new ProfileResource($this->http);
        $this->postingCategories = new PostingCategoryResource($this->http);
        $this->countries = new CountryResource($this->http);
        $this->financialAccounts = new FinancialAccountResource($this->http);
        $this->financialTransactions = new FinancialTransactionResource($this->http);
        $this->transactionAssignmentHints = new TransactionAssignmentHintResource($this->http);
        $this->partnerIntegrations = new PartnerIntegrationResource($this->http);
    }

    public function setOAuth2Service(LexwareOAuth2Service $oauth2Service): self
    {
        $this->http->setOAuth2Service($oauth2Service);

        return $this;
    }

    public function oauth2(): ?LexwareOAuth2Service
    {
        return $this->http->oauth2();
    }

    public function getOAuth2Service(): ?LexwareOAuth2Service
    {
        return $this->oauth2();
    }

    public function getOAuth2AuthorizationUrl(?string $state = null): LexwareAuthorizationUrl
    {
        $service = $this->oauth2();
        if (! $service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured', 500);
        }

        return $service->getAuthorizationUrl($state);
    }

    public function exchangeOAuth2CodeForToken(string $code, string $state): LexwareAccessToken
    {
        $service = $this->oauth2();
        if (! $service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured', 500);
        }

        return $service->exchangeCodeForToken($code, $state);
    }

    public function getValidOAuth2Token(): ?LexwareAccessToken
    {
        $service = $this->oauth2();
        if (! $service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured', 500);
        }

        return $service->getValidAccessToken();
    }

    public function revokeOAuth2Token(?string $token = null): bool
    {
        $service = $this->oauth2();
        if (! $service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured', 500);
        }

        return $service->revokeToken($token);
    }

    public function hasOAuth2(): bool
    {
        return $this->oauth2() !== null;
    }

    public function setRateLimitKey(string $key): void
    {
        $this->http->setRateLimitKey($key);
    }

    public function getRateLimitKey(): string
    {
        return $this->http->getRateLimitKey();
    }

    public function setRateLimit(int $maxRequests): self
    {
        $this->http->setMaxRequestsPerMinute($maxRequests);

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Back-compat helper for existing tests that inject a mocked Guzzle client.
     */
    public function setClient(ClientInterface $client): self
    {
        $this->http->setTransport(new GuzzleTransport($client));

        return $this;
    }

    public function contacts(): ContactResource
    {
        return $this->contacts;
    }

    public function vouchers(): VoucherResource
    {
        return $this->vouchers;
    }

    public function profile(): ProfileResource
    {
        return $this->profile;
    }

    public function postingCategories(): PostingCategoryResource
    {
        return $this->postingCategories;
    }

    public function countries(): CountryResource
    {
        return $this->countries;
    }

    public function financialAccounts(): FinancialAccountResource
    {
        return $this->financialAccounts;
    }

    public function financialTransactions(): FinancialTransactionResource
    {
        return $this->financialTransactions;
    }

    public function transactionAssignmentHints(): TransactionAssignmentHintResource
    {
        return $this->transactionAssignmentHints;
    }

    public function partnerIntegrations(): PartnerIntegrationResource
    {
        return $this->partnerIntegrations;
    }
}


