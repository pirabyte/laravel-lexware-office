<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialAccountState implements Dto
{
    public function __construct(
        public string $organizationId,
        public string $status,
        public ?string $errorMessage,
        public bool $errorOnSync,
        public ?DateTimeImmutable $syncStartDate,
        public ?DateTimeImmutable $syncEndDate,
    ) {
        Assert::nonEmptyString($this->organizationId, 'FinancialAccountState.organizationId must be non-empty');
        Assert::nonEmptyString($this->status, 'FinancialAccountState.status must be non-empty');
    }
}


