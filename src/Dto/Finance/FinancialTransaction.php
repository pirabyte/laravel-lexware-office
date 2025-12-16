<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Enums\TransactionState;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialTransaction implements Dto
{
    public function __construct(
        public string $financialTransactionId,
        public DateTimeImmutable $valueDate,
        public ?DateTimeImmutable $bookingDate,
        public DateTimeImmutable $transactionDate,
        public string $purpose,
        public float $amount,
        public ?float $openAmount,
        public ?string $amountAsString,
        public ?string $openAmountAsString,
        public ?string $additionalInfo,
        public ?TransactionState $state,
        public ?string $recipientOrSenderName,
        public ?string $recipientOrSenderEmail,
        public ?string $recipientOrSenderIban,
        public ?string $recipientOrSenderBic,
        public string $financialAccountId,
        public ?string $externalReference,
        public ?string $endToEndId,
        public int $lockVersion,
    ) {
        Assert::nonEmptyString($this->financialTransactionId, 'FinancialTransaction.financialTransactionId must be non-empty');
        Assert::nonEmptyString($this->purpose, 'FinancialTransaction.purpose must be non-empty');
        Assert::nonEmptyString($this->financialAccountId, 'FinancialTransaction.financialAccountId must be non-empty');
        Assert::intRange($this->lockVersion, 0, PHP_INT_MAX, 'FinancialTransaction.lockVersion must be >= 0');
    }
}


