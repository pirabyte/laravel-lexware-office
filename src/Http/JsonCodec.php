<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;

/**
 * @internal
 */
final class JsonCodec
{
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public static function decode(string $json): array
    {
        try {
            /** @var array<string, mixed>|list<mixed> $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (\JsonException $e) {
            throw new DecodeException('Invalid JSON response', $json, $e);
        }
    }

    /**
     * @param  mixed  $value
     */
    public static function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Could not encode JSON', 0, $e);
        }
    }
}


