<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

final readonly class RetryDecision
{
    private function __construct(
        public bool $shouldRetry,
        public int $delayMicroseconds,
    ) {}

    public static function no(): self
    {
        return new self(false, 0);
    }

    public static function yes(int $delayMicroseconds): self
    {
        return new self(true, max(0, $delayMicroseconds));
    }
}


