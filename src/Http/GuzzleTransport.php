<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final readonly class GuzzleTransport implements Transport
{
    public function __construct(private ClientInterface $client) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function request(HttpMethod $method, string $endpoint, array $options = []): ResponseInterface
    {
        return $this->client->request($method->value, $endpoint, $options);
    }
}


