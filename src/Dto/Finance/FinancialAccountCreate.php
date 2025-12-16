<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialAccountCreate implements Dto
{
    public function __construct(
        public string $financialAccountId,
        public FinancialAccountType $type,
        public AccountSystem $accountSystem,
        public string $name,
        public ?string $bankName = null,
        public ?string $externalReference = null,
    ) {
        Assert::nonEmptyString($this->financialAccountId, 'FinancialAccountCreate.financialAccountId must be non-empty');
        Assert::nonEmptyString($this->name, 'FinancialAccountCreate.name must be non-empty');
    }
}


