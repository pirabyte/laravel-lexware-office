<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

final class UsleepSleeper implements Sleeper
{
    public function usleep(int $microseconds): void
    {
        if ($microseconds <= 0) {
            return;
        }

        \usleep($microseconds);
    }
}


