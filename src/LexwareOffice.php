<?php

namespace Pirabyte\LaravelLexwareOffice;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;

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
    public ContactResource $contacts;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->contacts = new ContactResource($this);
    }

    #region Contacts

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
            return $this->makeRequest(function () use ($endpoint, $query) {
                return $this->client->get($endpoint, ['query' => $query]);
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
            $response = $this->client->post($endpoint, [
                'json' => $data
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        }
    }

    #endregion Contacts

    #region Helper

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