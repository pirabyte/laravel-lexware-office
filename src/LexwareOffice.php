<?php

namespace Pirabyte\LaravelLexwareOffice;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\CountryResource;
use Pirabyte\LaravelLexwareOffice\Resources\CreditNoteResource;
use Pirabyte\LaravelLexwareOffice\Resources\EventSubscriptionResource;
use Pirabyte\LaravelLexwareOffice\Resources\FileResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialAccountResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialTransactionResource;
use Pirabyte\LaravelLexwareOffice\Resources\InvoiceResource;
use Pirabyte\LaravelLexwareOffice\Resources\PartnerIntegrationResource;
use Pirabyte\LaravelLexwareOffice\Resources\PaymentResource;
use Pirabyte\LaravelLexwareOffice\Resources\PostingCategoryResource;
use Pirabyte\LaravelLexwareOffice\Resources\PrintLayoutResource;
use Pirabyte\LaravelLexwareOffice\Resources\ProfileResource;
use Pirabyte\LaravelLexwareOffice\Resources\TransactionAssignmentHintResource;
use Pirabyte\LaravelLexwareOffice\Resources\VoucherResource;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;
use Psr\Http\Message\ResponseInterface;

class LexwareOffice
{
    protected Client $client;

    protected Client $httpClient;

    protected string $baseUrl;

    protected string $apiKey;

    protected string $rateLimitKey = 'lexware_office_api';

    protected int $maxRequestsPerMinute = 50;

    protected InvoiceResource $invoices;

    protected CreditNoteResource $creditNotes;

    protected PrintLayoutResource $printLayouts;

    protected PaymentResource $payments;

    protected EventSubscriptionResource $eventSubscriptions;

    protected FileResource $files;

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

        // Alias for tests that use reflection to access httpClient
        $this->httpClient = $this->client;

        $this->invoices = new InvoiceResource($this);
        $this->creditNotes = new CreditNoteResource($this);
        $this->printLayouts = new PrintLayoutResource($this);
        $this->payments = new PaymentResource($this);
        $this->eventSubscriptions = new EventSubscriptionResource($this);
        $this->files = new FileResource($this);
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

    public function invoices(): InvoiceResource
    {
        return $this->invoices;
    }

    public function creditNotes(): CreditNoteResource
    {
        return $this->creditNotes;
    }

    public function printLayouts(): PrintLayoutResource
    {
        return $this->printLayouts;
    }

    public function payments(): PaymentResource
    {
        return $this->payments;
    }

    public function eventSubscriptions(): EventSubscriptionResource
    {
        return $this->eventSubscriptions;
    }

    public function files(): FileResource
    {
        return $this->files;
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

    /**
     * Get OAuth2 service instance
     */
    public function getOAuth2Service(): ?LexwareOAuth2Service
    {
        return $this->oauth2Service;
    }

    /**
     * Get OAuth2 authorization URL
     */
    public function getOAuth2AuthorizationUrl(?string $state = null)
    {
        if (! $this->oauth2Service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured');
        }

        return $this->oauth2Service->getAuthorizationUrl($state);
    }

    /**
     * Exchange authorization code for token
     */
    public function exchangeOAuth2CodeForToken(string $code, string $state)
    {
        if (! $this->oauth2Service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured');
        }

        return $this->oauth2Service->exchangeCodeForToken($code, $state);
    }

    /**
     * Get valid OAuth2 token
     */
    public function getValidOAuth2Token()
    {
        if (! $this->oauth2Service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured');
        }

        return $this->oauth2Service->getValidAccessToken();
    }

    /**
     * Revoke OAuth2 token
     */
    public function revokeOAuth2Token(?string $token = null): bool
    {
        if (! $this->oauth2Service) {
            throw new LexwareOfficeApiException('OAuth2 service not configured');
        }

        return $this->oauth2Service->revokeToken($token);
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
    public function post(string $endpoint, array $data, array $query = []): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $data, $query) {
                $options = ['json' => $data];
                if ($query !== []) {
                    $options['query'] = $query;
                }

                return $this->client->post($endpoint, $options);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    public function download(string $endpoint, FileFormat $format = FileFormat::DEFAULT): FileDownload
    {
        $this->ensureRateLimitAvailable();
        $response = $this->executeResponse(function () use ($endpoint, $format) {
            return $this->client->get($endpoint, [
                'headers' => ['Accept' => $format->value],
                'stream' => true,
            ]);
        });

        return FileDownload::fromResponse($response);
    }

    /**
     * POST-Anfrage mit Multipart-Daten (für Datei-Uploads)
     *
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function postMultipart(string $endpoint, array $multipartData): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $multipartData) {
                return $this->client->post($endpoint, [
                    'multipart' => $multipartData,
                ]);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * Wait until the local Laravel rate limiter has enough capacity.
     *
     * This only coordinates the package's local limiter. It does not reserve
     * capacity and does not guarantee that Lexware's remote token bucket will
     * accept the following request.
     *
     * @param  int  $requiredAttempts  Number of upcoming HTTP attempts to wait for.
     * @param  int|null  $maxWaitSeconds  Maximum seconds to wait before throwing a local rate-limit exception.
     * @param  int|null  $maxAttempts  Maximum limiter checks to perform before throwing a local rate-limit exception.
     *
     * @throws LexwareOfficeApiException
     */
    public function waitForRateLimitCapacity(int $requiredAttempts, ?int $maxWaitSeconds = 120, ?int $maxAttempts = null): void
    {
        if ($this->maxRequestsPerMinute <= 0 || $requiredAttempts <= 0) {
            return;
        }

        $requiredAttempts = min($requiredAttempts, $this->maxRequestsPerMinute);
        $startedAt = microtime(true);
        $attempts = 0;

        while (true) {
            $attempts++;

            if (RateLimiter::tooManyAttempts($this->rateLimitKey, $this->maxRequestsPerMinute)) {
                $this->throwIfRateLimitWaitExceeded($startedAt, $attempts, $maxWaitSeconds, $maxAttempts);
                $this->sleepUntilRateLimitAvailable($startedAt, $maxWaitSeconds);
                $this->throwIfRateLimitWaitExceeded($startedAt, $attempts, $maxWaitSeconds, $maxAttempts);

                continue;
            }

            if (RateLimiter::remaining($this->rateLimitKey, $this->maxRequestsPerMinute) >= $requiredAttempts) {
                return;
            }

            $this->throwIfRateLimitWaitExceeded($startedAt, $attempts, $maxWaitSeconds, $maxAttempts);
            $this->sleepUntilRateLimitAvailable($startedAt, $maxWaitSeconds);
            $this->throwIfRateLimitWaitExceeded($startedAt, $attempts, $maxWaitSeconds, $maxAttempts);
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
     * @param  RequestException  $e  The original request exception
     * @return LexwareOfficeApiException The standardized API exception
     */
    protected function handleRequestException(RequestException $e): LexwareOfficeApiException
    {
        $response = $e->getResponse();

        if (! $response) {
            // No response from the server - likely a connection error
            return new LexwareOfficeApiException(
                'Could not connect to the Lexware Office API: '.$e->getMessage(),
                500,
                $e
            );
        }

        $statusCode = $response->getStatusCode();
        $message = $response->getBody()->getContents();

        // Special handling for rate limit errors
        if ($statusCode === 429) {
            $retryAfter = $response->hasHeader('Retry-After')
                ? (int) $response->getHeaderLine('Retry-After')
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
     * @param  callable  $callback  The request callback to execute
     * @return array The decoded JSON response data
     *
     * @throws LexwareOfficeApiException
     */
    protected function makeRequest(callable $callback)
    {
        $this->ensureRateLimitAvailable();

        return $this->executeRequest($callback);
    }

    protected function ensureRateLimitAvailable(): void
    {
        if ($this->maxRequestsPerMinute > 0 && RateLimiter::tooManyAttempts($this->rateLimitKey, $this->maxRequestsPerMinute)) {
            $seconds = RateLimiter::availableIn($this->rateLimitKey);

            throw new LexwareOfficeApiException(json_encode([
                'message' => 'Rate limit exceeded',
                'details' => "Too many requests. Please wait {$seconds} seconds before retrying.",
                'retryAfter' => $seconds,
            ]), LexwareOfficeApiException::STATUS_RATE_LIMITED);
        }
    }

    /** @return array<string, mixed> */
    protected function executeRequest(callable $callback): array
    {
        $content = $this->executeResponse($callback)->getBody()->getContents();
        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : ['raw' => $content];
    }

    protected function executeResponse(callable $callback): ResponseInterface
    {
        $this->ensureValidToken();

        try {
            $response = $callback();
        } catch (RequestException $e) {
            if (! $this->oauth2Service || $e->getResponse()?->getStatusCode() !== 401 || ! $this->refreshTokenAndRetry()) {
                throw $this->handleRequestException($e);
            }

            try {
                $response = $callback();
            } catch (RequestException $retryException) {
                // Preserve the existing JSON transport's retry error behavior.
                throw $this->handleRequestException($e);
            }
        }

        if ($this->maxRequestsPerMinute > 0) {
            RateLimiter::hit($this->rateLimitKey, 60);
        }

        return $response;
    }

    /**
     * Sleep until the local rate limiter is expected to have capacity.
     *
     * @throws LexwareOfficeApiException
     */
    protected function sleepUntilRateLimitAvailable(float $startedAt, ?int $maxWaitSeconds): void
    {
        $seconds = (float) max(1, RateLimiter::availableIn($this->rateLimitKey));

        if ($maxWaitSeconds !== null) {
            $remainingSeconds = $maxWaitSeconds - (microtime(true) - $startedAt);
            if ($remainingSeconds <= 0) {
                throw $this->localRateLimitWaitExceededException();
            }

            $seconds = min($seconds, $remainingSeconds);
        }

        usleep((int) ceil($seconds * 1_000_000));
    }

    /**
     * @throws LexwareOfficeApiException
     */
    protected function throwIfRateLimitWaitExceeded(
        float $startedAt,
        int $attempts,
        ?int $maxWaitSeconds,
        ?int $maxAttempts,
    ): void {
        if ($maxAttempts !== null && $attempts >= $maxAttempts) {
            throw $this->localRateLimitWaitExceededException();
        }

        if ($maxWaitSeconds !== null && microtime(true) - $startedAt >= $maxWaitSeconds) {
            throw $this->localRateLimitWaitExceededException();
        }
    }

    protected function localRateLimitWaitExceededException(): LexwareOfficeApiException
    {
        $seconds = RateLimiter::availableIn($this->rateLimitKey);

        return new LexwareOfficeApiException(json_encode([
            'message' => 'Rate limit wait exceeded',
            'details' => 'Timed out while waiting for local rate limit capacity.',
            'retryAfter' => $seconds,
        ]), LexwareOfficeApiException::STATUS_RATE_LIMITED);
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
        if (! $this->oauth2Service) {
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
        if (! $this->oauth2Service) {
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
