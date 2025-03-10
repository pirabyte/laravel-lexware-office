<?php

namespace Pirabyte\LaravelLexwareOffice;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\CountryResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialAccountResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialTransactionResource;
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

    /**
     * @var ContactResource
     */
    protected ContactResource $contacts;

    /**
     * @var VoucherResource
     */
    protected VoucherResource $vouchers;

    /**
     * @var ProfileResource
     */
    protected ProfileResource $profile;

    /**
     * @var PostingCategoryResource
     */
    protected PostingCategoryResource $postingCategories;

    /**
     * @var CountryResource
     */
    protected CountryResource $countries;

    /**
     * @var FinancialAccountResource
     */
    protected FinancialAccountResource $financialAccounts;

    /**
     * @var FinancialTransactionResource
     */
    protected FinancialTransactionResource $financialTransactions;
    
    /**
     * @var TransactionAssignmentHintResource
     */
    protected TransactionAssignmentHintResource $transactionAssignmentHints;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;

        $uri = $this->prepareBaseUri($this->baseUrl);

        $this->client = new Client([
            'base_uri' => $uri,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
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
    }

    #region Contacts

    /**
     * Kontakt-Ressource abrufen
     */
    public function contacts(): ContactResource
    {
        return $this->contacts;
    }

    #endregion Contacts

    #region Vouchers

    /**
     * Beleg-Ressource abrufen
     */
    public function vouchers(): VoucherResource
    {
        return $this->vouchers;
    }

    #endregion Vouchers

    #region Profile

    /**
     * Profil-Ressource abrufen
     */
    public function profile(): ProfileResource
    {
        return $this->profile;
    }

    #endregion Profile

    #region PostingCategories

    /**
     * Buchungskategorien-Ressource abrufen
     */
    public function postingCategories(): PostingCategoryResource
    {
        return $this->postingCategories;
    }

    #endregion PostingCategories

    #region Countries

    /**
     * Länder-Ressource abrufen
     */
    public function countries(): CountryResource
    {
        return $this->countries;
    }

    #endregion Countries

    #region FinancialAccounts

    /**
     * Finanzkonten-Ressource abrufen
     */
    public function financialAccounts(): FinancialAccountResource
    {
        return $this->financialAccounts;
    }

    #endregion FinancialAccounts

    #region FinancialTransactions

    /**
     * Finanztransaktionen-Ressource abrufen
     */
    public function financialTransactions(): FinancialTransactionResource
    {
        return $this->financialTransactions;
    }

    #endregion FinancialTransactions
    
    #region TransactionAssignmentHints
    
    /**
     * TransactionAssignmentHint-Ressource abrufen
     */
    public function transactionAssignmentHints(): TransactionAssignmentHintResource
    {
        return $this->transactionAssignmentHints;
    }
    
    #endregion TransactionAssignmentHints

    #region Requests

    /**
     * GET-Anfrage
     *
     * @param string $endpoint
     * @param array $query
     * @return array
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
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $data) {
                return $this->client->post($endpoint, [
                    'json' => $data
                ]);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * PUT-Anfrage
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function put(string $endpoint, array $data): array
    {
        try {
            return $this->makeRequest(function () use ($endpoint, $data) {
                return $this->client->put($endpoint, [
                    'json' => $data
                ]);
            });
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    /**
     * DELETE-Anfrage
     *
     * @param string $endpoint
     * @return void
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

    #endregion Requests

    #region Helper

    /**
     * Bereitet die Basis-URI für API-Requests vor.
     * Stellt sicher, dass die URL mit /v1/ endet.
     *
     * @param string $baseUrl Die Basis-URL für die API
     * @return string Die korrekt formatierte Basis-URL
     */
    protected function prepareBaseUri(string $baseUrl): string
    {
        // Entferne trailing slashes
        $baseUrl = rtrim($baseUrl, '/');

        // Prüfe, ob die URL bereits mit /v1 endet
        if (!str_ends_with($baseUrl, '/v1')) {
            // Wenn nicht, füge /v1 hinzu
            $baseUrl .= '/v1';
        }

        // Stelle sicher, dass die URL mit einem Slash endet
        return $baseUrl . '/';
    }

    /**
     * Behandelt Anfrage-Exceptions
     *
     * @param RequestException $e
     * @return LexwareOfficeApiException
     */
    protected function handleRequestException(RequestException $e): LexwareOfficeApiException
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 500;
        $message = $response ? $response->getBody()->getContents() : $e->getMessage();

        return new LexwareOfficeApiException($message, $statusCode, $e);
    }

    /**
     * Setzt den HTTP-Client (für Tests)
     *
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;
        return $this;
    }
    
    /**
     * Gibt den HTTP-Client zurück
     *
     * @return Client
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Führt eine Anfrage mit Rate Limiting aus
     * @throws LexwareOfficeApiException
     */
    protected function makeRequest(callable $callback)
    {
        if (RateLimiter::tooManyAttempts($this->rateLimitKey, $this->maxRequestsPerMinute)) {
            $seconds = RateLimiter::availableIn($this->rateLimitKey);
            throw new LexwareOfficeApiException(
                "Rate limit erreicht. Bitte warten Sie {$seconds} Sekunden.",
                429
            );
        }

        try {
            $response = $callback();
            RateLimiter::hit($this->rateLimitKey, 60);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
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

    #endregion Helper

}