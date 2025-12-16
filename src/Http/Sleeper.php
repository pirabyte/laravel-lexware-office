<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

interface Sleeper
{
    public function usleep(int $microseconds): void;
}


