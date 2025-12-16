<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Support;

use InvalidArgumentException;

final class Assert
{
    private function __construct() {}

    public static function string(mixed $value, string $message = 'Expected string'): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    public static function nullableString(mixed $value, string $message = 'Expected string or null'): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::string($value, $message);
    }

    public static function int(mixed $value, string $message = 'Expected int'): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException($message);
    }

    public static function nullableInt(mixed $value, string $message = 'Expected int or null'): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::int($value, $message);
    }

    public static function float(mixed $value, string $message = 'Expected float'): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException($message);
    }

    public static function nullableFloat(mixed $value, string $message = 'Expected float or null'): ?float
    {
        if ($value === null) {
            return null;
        }

        return self::float($value, $message);
    }

    public static function bool(mixed $value, string $message = 'Expected bool'): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            if ($value === 'true') {
                return true;
            }
            if ($value === 'false') {
                return false;
            }
        }

        throw new InvalidArgumentException($message);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public static function array(mixed $value, string $message = 'Expected array'): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    public static function nonEmptyString(string $value, string $message = 'Expected non-empty string'): string
    {
        if ($value === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    public static function positiveInt(int $value, string $message = 'Expected positive integer'): int
    {
        if ($value <= 0) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    public static function intRange(int $value, int $min, int $max, string $message = 'Integer out of range'): int
    {
        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }
}


