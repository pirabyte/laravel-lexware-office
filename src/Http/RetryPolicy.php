<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\TransportException;

interface RetryPolicy
{
    public function decide(
        HttpMethod $method,
        int $attempt,
        ?LexwareOfficeApiException $apiException,
        ?TransportException $transportException
    ): RetryDecision;
}


