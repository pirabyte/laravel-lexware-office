<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\TransactionAssignmentHints;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class TransactionAssignmentHint implements Dto
{
    public function __construct(
        public string $voucherId,
        public string $externalReference,
    ) {
        Assert::nonEmptyString($this->voucherId, 'TransactionAssignmentHint.voucherId must be non-empty');
        Assert::nonEmptyString($this->externalReference, 'TransactionAssignmentHint.externalReference must be non-empty');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'voucherId' => $this->voucherId,
            'externalReference' => $this->externalReference,
        ];
    }
}


