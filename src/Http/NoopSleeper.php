<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

/**
 * Test helper.
 *
 * @internal
 */
final class NoopSleeper implements Sleeper
{
    public function usleep(int $microseconds): void
    {
        // no-op
    }
}


