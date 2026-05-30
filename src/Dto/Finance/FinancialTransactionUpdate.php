<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialTransactionUpdate implements Dto
{
    public function __construct(
        public int $lockVersion,
        public ?DateTimeImmutable $valueDate = null,
        public ?DateTimeImmutable $bookingDate = null,
        public ?DateTimeImmutable $transactionDate = null,
        public ?string $purpose = null,
        public ?float $amount = null,
        public ?string $financialAccountId = null,
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
        Assert::intRange($this->lockVersion, 0, PHP_INT_MAX, 'FinancialTransactionUpdate.lockVersion must be >= 0');
    }
}


