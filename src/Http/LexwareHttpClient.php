<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\TransportException;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;

/**
 * Internal HTTP client with retry/backoff, Laravel rate-limiting and optional OAuth2 token management.
 *
 * @internal
 */
final class LexwareHttpClient
{
    private Transport $transport;

    private ?LexwareOAuth2Service $oauth2Service = null;

    public function __construct(
        string $baseUrl,
        private readonly string $apiKey,
        private string $rateLimitKey = 'lexware_office_api',
        private int $maxRequestsPerMinute = 50,
        private float $timeoutSeconds = 30.0,
        ?Transport $transport = null,
        private readonly RetryPolicy $retryPolicy = new ExponentialBackoffRetryPolicy(maxAttempts: 1),
        private readonly Sleeper $sleeper = new UsleepSleeper(),
    ) {
        $uri = $this->prepareBaseUri($baseUrl);

        $this->transport = $transport ?? new GuzzleTransport(new Client([
            'base_uri' => $uri,
            'timeout' => $this->timeoutSeconds,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]));
    }

    public function setTransport(Transport $transport): void
    {
        $this->transport = $transport;
    }

    public function setOAuth2Service(?LexwareOAuth2Service $oauth2Service): void
    {
        $this->oauth2Service = $oauth2Service;
    }

    public function oauth2(): ?LexwareOAuth2Service
    {
        return $this->oauth2Service;
    }

    public function setRateLimitKey(string $rateLimitKey): void
    {
        $this->rateLimitKey = $rateLimitKey;
    }

    public function getRateLimitKey(): string
    {
        return $this->rateLimitKey;
    }

    public function setMaxRequestsPerMinute(int $maxRequestsPerMinute): void
    {
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;
    }

    public function setTimeoutSeconds(float $timeoutSeconds): void
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function get(string $endpoint, ?QueryParams $query = null): HttpResponse
    {
        return $this->send(HttpMethod::GET, $endpoint, $query, null, null);
    }

    public function delete(string $endpoint, ?QueryParams $query = null): HttpResponse
    {
        return $this->send(HttpMethod::DELETE, $endpoint, $query, null, null);
    }

    public function postJson(string $endpoint, JsonBody $body, ?QueryParams $query = null): HttpResponse
    {
        return $this->send(HttpMethod::POST, $endpoint, $query, $body, null);
    }

    public function putJson(string $endpoint, JsonBody $body, ?QueryParams $query = null): HttpResponse
    {
        return $this->send(HttpMethod::PUT, $endpoint, $query, $body, null);
    }

    public function postMultipart(string $endpoint, MultipartBody $multipart, ?QueryParams $query = null): HttpResponse
    {
        return $this->send(HttpMethod::POST, $endpoint, $query, null, $multipart);
    }

    private function send(
        HttpMethod $method,
        string $endpoint,
        ?QueryParams $query,
        ?JsonBody $jsonBody,
        ?MultipartBody $multipartBody,
    ): HttpResponse {
        $attempt = 1;
        $refreshedToken = false;

        while (true) {
            $this->enforceLocalRateLimit();

            $options = [
                'timeout' => $this->timeoutSeconds,
                'headers' => $this->buildHeaders($jsonBody, $multipartBody),
            ];

            if ($query !== null) {
                $options['query'] = $this->toQueryArray($query);
            }

            if ($jsonBody !== null) {
                $options['headers']['Content-Type'] = 'application/json';
                $options['body'] = $jsonBody->json;
            }

            if ($multipartBody !== null) {
                $options['multipart'] = $this->toMultipartArray($multipartBody);
            }

            try {
                // Count every outgoing attempt against our own limiter.
                RateLimiter::hit($this->rateLimitKey, 60);

                $response = $this->transport->request($method, $endpoint, $options);

                return new HttpResponse(
                    statusCode: $response->getStatusCode(),
                    body: (string) $response->getBody(),
                    headers: $response->getHeaders(),
                );
            } catch (RequestException $e) {
                // Network error (no response)
                if (! $e->getResponse()) {
                    $transportException = new TransportException('Network/transport error: '.$e->getMessage(), $e);
                    $decision = $this->retryPolicy->decide($method, $attempt, null, $transportException);
                    if ($decision->shouldRetry) {
                        $this->sleeper->usleep($decision->delayMicroseconds);
                        $attempt++;
                        continue;
                    }

                    throw $transportException;
                }

                $statusCode = $e->getResponse()->getStatusCode();
                $rawBody = (string) $e->getResponse()->getBody();

                $apiException = new LexwareOfficeApiException($rawBody, $statusCode, $e);

                // OAuth2: on 401, try refresh once, then retry.
                if ($apiException->isAuthError() && $this->oauth2Service && ! $refreshedToken) {
                    try {
                        $this->oauth2Service->refreshToken();
                        $refreshedToken = true;
                        $attempt++;
                        continue;
                    } catch (LexwareOfficeApiException $refreshException) {
                        throw $apiException;
                    }
                }

                $decision = $this->retryPolicy->decide($method, $attempt, $apiException, null);
                if ($decision->shouldRetry) {
                    $this->sleeper->usleep($decision->delayMicroseconds);
                    $attempt++;
                    continue;
                }

                throw $apiException;
            } catch (GuzzleException $e) {
                $transportException = new TransportException('Transport error: '.$e->getMessage(), $e);
                $decision = $this->retryPolicy->decide($method, $attempt, null, $transportException);
                if ($decision->shouldRetry) {
                    $this->sleeper->usleep($decision->delayMicroseconds);
                    $attempt++;
                    continue;
                }

                throw $transportException;
            }
        }
    }

    /**
     * Prepare base URI and ensure it ends with `/v1/`.
     */
    private function prepareBaseUri(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (! str_ends_with($baseUrl, '/v1')) {
            $baseUrl .= '/v1';
        }

        return $baseUrl.'/';
    }

    private function enforceLocalRateLimit(): void
    {
        if (! RateLimiter::tooManyAttempts($this->rateLimitKey, $this->maxRequestsPerMinute)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->rateLimitKey);

        $rawBody = JsonCodec::encode([
            'message' => 'Rate limit exceeded',
            'details' => "Too many requests. Please wait {$seconds} seconds before retrying.",
            'retryAfter' => $seconds,
        ]);

        throw new LexwareOfficeApiException($rawBody, LexwareOfficeApiException::STATUS_RATE_LIMITED);
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(?JsonBody $jsonBody, ?MultipartBody $multipartBody): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => $this->getAuthorizationHeader(),
        ];

        // JSON sets Content-Type explicitly; multipart is handled by Guzzle.
        if ($jsonBody !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    private function getAuthorizationHeader(): string
    {
        if (! $this->oauth2Service) {
            return 'Bearer '.$this->apiKey;
        }

        $token = $this->oauth2Service->getValidAccessToken();
        if ($token) {
            return $token->getAuthorizationHeader();
        }

        return 'Bearer '.$this->apiKey;
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function toQueryArray(QueryParams $query): array
    {
        $result = [];

        foreach ($query as $param) {
            /** @var QueryParam $param */
            if (! array_key_exists($param->key, $result)) {
                $result[$param->key] = $param->value;
                continue;
            }

            $existing = $result[$param->key];
            if (is_array($existing)) {
                $existing[] = $param->value;
                $result[$param->key] = $existing;
                continue;
            }

            $result[$param->key] = [$existing, $param->value];
        }

        return $result;
    }

    /**
     * @return list<array{name:string,contents:mixed,filename?:string}>
     */
    private function toMultipartArray(MultipartBody $multipart): array
    {
        $result = [];

        foreach ($multipart as $part) {
            /** @var MultipartPart $part */
            $entry = [
                'name' => $part->name,
                'contents' => $part->contents,
            ];

            if ($part->filename !== null) {
                $entry['filename'] = $part->filename;
            }

            $result[] = $entry;
        }

        return $result;
    }
}


