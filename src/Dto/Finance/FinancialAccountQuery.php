<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;

final readonly class FinancialAccountQuery implements Dto
{
    public function __construct(
        public ?string $iban = null,
        public ?string $externalReference = null,
    ) {}
}


