<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
interface Transport
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function request(HttpMethod $method, string $endpoint, array $options = []): ResponseInterface;
}


