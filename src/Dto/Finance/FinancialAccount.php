<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\StringList;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class FinancialAccount implements Dto
{
    public function __construct(
        public string $organizationId,
        public FinancialAccountType $type,
        public string $name,
        public ?string $bankName,
        public ?float $balance,
        public bool $balanceAccessible,
        public string $financialAccountId,
        public FinancialAccountState $state,
        public ?string $externalReference,
        public ?DateTimeImmutable $initialSyncDate,
        public int $lockVersion,
        public AccountSystem $accountSystem,
        public StringList $availableActions,
        public bool $connected,
        public bool $deactivated,
        public bool $synchronizable,
        public bool $canIgnoreTransactions,
        public DateTimeImmutable $createdDate,
        public DateTimeImmutable $lastModifiedDate,
        public ?string $virtualAccountId,
        public ?string $brandColor,
        public ?string $brandLogoUrlLight,
        public ?string $brandLogoUrlDark,
        public ?string $brandLogoUrlColor,
        public ?string $brandLogoUrlAvatar,
        public ?string $usingProfileType,
    ) {
        Assert::nonEmptyString($this->organizationId, 'FinancialAccount.organizationId must be non-empty');
        Assert::nonEmptyString($this->name, 'FinancialAccount.name must be non-empty');
        Assert::nonEmptyString($this->financialAccountId, 'FinancialAccount.financialAccountId must be non-empty');
        Assert::intRange($this->lockVersion, 0, PHP_INT_MAX, 'FinancialAccount.lockVersion must be >= 0');
    }
}


