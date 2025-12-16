<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

/**
 * @internal
 */
final readonly class HttpResponse
{
    /**
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers,
    ) {}

    public function headerLine(string $name): ?string
    {
        $values = $this->headers[$name] ?? null;
        if (! is_array($values) || $values === []) {
            return null;
        }

        return $values[0] ?? null;
    }
}


