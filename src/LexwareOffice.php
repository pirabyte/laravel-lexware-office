<?php

namespace Pirabyte\LaravelLexwareOffice;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
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

class LexwareOffice
{
    protected Client $client;

    protected string $baseUrl;

    protected string $apiKey;

    protected string $rateLimitKey = 'lexware_office_api';

    protected int $maxRequestsPerMinute = 50;

    protected ContactResource $contacts;

    protected VoucherResource $vouchers;

    protected ProfileResource $profile;

    protected PostingCategoryResource $postingCategories;

    protected CountryResource $countries;

    protected FinancialAccountResource $financialAccounts;

    protected FinancialTransactionResource $financialTransactions;

    protected TransactionAssignmentHintResource $transactionAssignmentHints;

    protected PartnerIntegrationResource $partnerIntegrations;

    protected ?LexwareOAuth2Service $oauth2Service = null;

    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $rateLimitKey = 'lexware_office_api',
        int $maxRequestsPerMinute = 50
    ) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->rateLimitKey = $rateLimitKey;
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;

        $uri = $this->prepareBaseUri($this->baseUrl);

        $this->client = new Client([
            'base_uri' => $uri,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->contacts = new ContactResource($this);
        $this->vouchers = new VoucherResource($this);
        $this->profile = new ProfileResource($this);
        $this->postingCategories = new PostingCategoryResource($this);
        $this->countries = new CountryResource($this);
        $this->financialAccounts = new FinancialAccountResource($this);
        $this->financialTransactions = new FinancialTransactionResource($this);
        $this->transactionAssignmentHints = new TransactionAssignmentHintResource($this);
        $this->partnerIntegrations = new PartnerIntegrationResource($this);
    }

    /**
     * Set rate limit key for current client
     */
    public function setRateLimitKey(string $key): void
    {
        $this->rateLimitKey = $key;
    }

    // region Contacts

    /**
     * Kontakt-Ressource abrufen
     */
    public function contacts(): ContactResource
    {
        return $this->contacts;
    }

    // endregion Contacts

    // region Vouchers

    /**
     * Beleg-Ressource abrufen
     */
    public function vouchers(): VoucherResource
    {
        return $this->vouchers;
    }

    // endregion Vouchers

    // region Profile

    /**
     * Profil-Ressource abrufen
     */
    public function profile(): ProfileResource
    {
        return $this->profile;
    }

    // endregion Profile

    // region PostingCategories

    /**
     * Buchungskategorien-Ressource abrufen
     */
    public function postingCategories(): PostingCategoryResource
    {
        return $this->postingCategories;
    }

    // endregion PostingCategories

    // region Countries

    /**
     * Länder-Ressource abrufen
     */
    public function countries(): CountryResource
    {
        return $this->countries;
    }

    // endregion Countries

    // region FinancialAccounts

    /**
     * Finanzkonten-Ressource abrufen
     */
    public function financialAccounts(): FinancialAccountResource
    {
        return $this->financialAccounts;
    }

    // endregion FinancialAccounts

    // region FinancialTransactions

    /**
     * Finanztransaktionen-Ressource abrufen
     */
    public function financialTransactions(): FinancialTransactionResource
    {
        return $this->financialTransactions;
    }

    // endregion FinancialTransactions

    // region TransactionAssignmentHints

    /**
     * TransactionAssignmentHint-Ressource abrufen
     */
    public function transactionAssignmentHints(): TransactionAssignmentHintResource
    {
        return $this->transactionAssignmentHints;
    }

    // endregion TransactionAssignmentHints

    // region PartnerIntegrations

    /**
     * Retrieves the Partner Integration Resource
     */
    public function partnerIntegrations(): PartnerIntegrationResource
    {
        return $this->partnerIntegrations;
    }

    // endregion PartnerIntegrations

    // region OAuth2

    /**
     * Set OAuth2 service for automatic token management
     */
    public function setOAuth2Service(LexwareOAuth2Service $oauth2Service): self
    {
        $this->oauth2Service = $oauth2Service;
        return $this;
    }

    /**
     * Get OAuth2 service instance
     */
    public function oauth2(): ?LexwareOAuth2Service
    {
        return $this->oauth2Service;
    }

    /**
     * Check if OAuth2 is configured
     */
    public function hasOAuth2(): bool
    {
        return $this->oauth2Service !== null;
    }

    // endregion OAuth2

    // region Requests

    /**
     * GET-Anfrage
     *
     * @throws LexwareOfficeApiException
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            // Hier müssen wir sicherstellen, dass Array-Parameter korrekt als separate Query-Parameter gesendet werden
            $options = ['query' => $query];

            return $this->makeRequest(function () use ($endpoint, $options) {
                return $this->client->get($endpoint, $options);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * POST-Anfrage
     *
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $data) {
                return $this->client->post($endpoint, [
                    'json' => $data,
                ]);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * PUT-Anfrage
     *
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function put(string $endpoint, array $data): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $data) {
                return $this->client->put($endpoint, [
                    'json' => $data,
                ]);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * DELETE-Anfrage
     *
     * @throws LexwareOfficeApiException
     */
    public function delete(string $endpoint): void
    {
        try {
            $this->makeRequest(function () use ($endpoint) {
                return $this->client->delete($endpoint);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    // endregion Requests

    // region Helper

    /**
     * Bereitet die Basis-URI für API-Requests vor.
     * Stellt sicher, dass die URL mit /v1/ endet.
     *
     * @param  string  $baseUrl  Die Basis-URL für die API
     * @return string Die korrekt formatierte Basis-URL
     */
    protected function prepareBaseUri(string $baseUrl): string
    {
        // Entferne trailing slashes
        $baseUrl = rtrim($baseUrl, '/');

        // Prüfe, ob die URL bereits mit /v1 endet
        if (! str_ends_with($baseUrl, '/v1')) {
            // Wenn nicht, füge /v1 hinzu
            $baseUrl .= '/v1';
        }

        // Stelle sicher, dass die URL mit einem Slash endet
        return $baseUrl.'/';
    }

    /**
     * Handles request exceptions from the API
     *
     * This method processes exceptions from the Lexware Office API and
     * converts them into a standardized LexwareOfficeApiException format
     * with proper error information and type.
     *
     * @param RequestException $e The original request exception
     * @return LexwareOfficeApiException The standardized API exception
     */
    protected function handleRequestException(RequestException $e): LexwareOfficeApiException
    {
        $response = $e->getResponse();
        
        if (!$response) {
            // No response from the server - likely a connection error
            return new LexwareOfficeApiException(
                'Could not connect to the Lexware Office API: ' . $e->getMessage(),
                500,
                $e
            );
        }
        
        $statusCode = $response->getStatusCode();
        $message = $response->getBody()->getContents();
        
        // Special handling for rate limit errors
        if ($statusCode === 429) {
            $retryAfter = $response->hasHeader('Retry-After')
                ? (int)$response->getHeaderLine('Retry-After')
                : 60;
                
            // Add retry information to the message if it's a JSON response
            $responseData = json_decode($message, true);
            if (is_array($responseData)) {
                $responseData['retryAfter'] = $retryAfter;
                $message = json_encode($responseData);
            }
        }

        return new LexwareOfficeApiException($message, $statusCode, $e);
    }

    /**
     * Setzt den HTTP-Client (für Tests)
     *
     * @return $this
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gibt den HTTP-Client zurück
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Executes a request with rate limiting
     *
     * This method manages the application-side rate limiting and
     * executes API requests with proper error handling.
     *
     * @param callable $callback The request callback to execute
     * @return array The decoded JSON response data
     * @throws LexwareOfficeApiException
     */
    protected function makeRequest(callable $callback)
    {
        // Check if we've exceeded our self-imposed rate limit
        if (RateLimiter::tooManyAttempts($this->rateLimitKey, $this->maxRequestsPerMinute)) {
            $seconds = RateLimiter::availableIn($this->rateLimitKey);
            
            // Create a properly structured rate limit error
            $errorData = [
                'message' => 'Rate limit exceeded',
                'details' => "Too many requests. Please wait {$seconds} seconds before retrying.",
                'retryAfter' => $seconds
            ];
            
            throw new LexwareOfficeApiException(
                json_encode($errorData),
                LexwareOfficeApiException::STATUS_RATE_LIMITED
            );
        }

        // Ensure valid OAuth2 token if OAuth2 is configured
        $this->ensureValidToken();

        try {
            // Execute the request
            $response = $callback();
            
            // Track the request for our rate limiter
            RateLimiter::hit($this->rateLimitKey, 60);

            // Parse and return the response data
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle non-JSON responses
                return ['raw' => $content];
            }
            
            return $data;
        } catch (RequestException $e) {
            // Check if this is an authentication error and we have OAuth2
            if ($this->oauth2Service && $e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                // Try to refresh token and retry request once
                if ($this->refreshTokenAndRetry()) {
                    try {
                        $response = $callback();
                        RateLimiter::hit($this->rateLimitKey, 60);
                        
                        $content = $response->getBody()->getContents();
                        $data = json_decode($content, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return ['raw' => $content];
                        }
                        
                        return $data;
                    } catch (RequestException $retryException) {
                        // If retry also fails, throw the original exception
                        throw $this->handleRequestException($e);
                    }
                }
            }
            
            // Handle request exceptions (HTTP errors, etc.)
            throw $this->handleRequestException($e);
        }
    }

    /**
     * Konfiguriert das Rate-Limit
     */
    public function setRateLimit(int $maxRequests): static
    {
        $this->maxRequestsPerMinute = $maxRequests;

        return $this;
    }

    /**
     * Ensure we have a valid OAuth2 token
     */
    protected function ensureValidToken(): void
    {
        if (!$this->oauth2Service) {
            return; // No OAuth2 configured, use static API key
        }

        $token = $this->oauth2Service->getValidAccessToken();
        
        if ($token) {
            // Update client with fresh token
            $this->client = $this->client->getConfig('handler') ? 
                new Client(array_merge($this->client->getConfig(), [
                    'headers' => array_merge($this->client->getConfig('headers') ?? [], [
                        'Authorization' => $token->getAuthorizationHeader(),
                    ]),
                ])) :
                new Client([
                    'base_uri' => $this->client->getConfig('base_uri'),
                    'headers' => array_merge($this->client->getConfig('headers') ?? [], [
                        'Authorization' => $token->getAuthorizationHeader(),
                    ]),
                ]);
        }
    }

    /**
     * Try to refresh token and update client
     */
    protected function refreshTokenAndRetry(): bool
    {
        if (!$this->oauth2Service) {
            return false;
        }

        try {
            $token = $this->oauth2Service->refreshToken();
            
            if ($token) {
                // Update client with new token
                $this->ensureValidToken();
                return true;
            }
        } catch (LexwareOfficeApiException $e) {
            // Token refresh failed, can't retry
            return false;
        }

        return false;
    }

    // endregion Helper

}
