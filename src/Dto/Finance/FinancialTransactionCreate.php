<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialTransactionCreate implements Dto
{
    public function __construct(
        public DateTimeImmutable $valueDate,
        public DateTimeImmutable $bookingDate,
        public DateTimeImmutable $transactionDate,
        public string $purpose,
        public float $amount,
        public string $financialAccountId,
        public ?string $externalReference = null,
        public ?string $additionalInfo = null,
        public ?string $recipientOrSenderName = null,
        public ?string $recipientOrSenderEmail = null,
        public ?string $recipientOrSenderIban = null,
        public ?string $recipientOrSenderBic = null,
        public ?float $feeAmount = null,
        public ?float $feeTaxRatePercentage = null,
        public ?string $feePostingCategoryId = null,
    ) {
        Assert::nonEmptyString($this->purpose, 'FinancialTransactionCreate.purpose must be non-empty');
        Assert::nonEmptyString($this->financialAccountId, 'FinancialTransactionCreate.financialAccountId must be non-empty');
    }
}


