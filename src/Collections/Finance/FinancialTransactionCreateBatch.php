<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Finance;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionCreate;

/**
 * @extends TypedCollection<FinancialTransactionCreate>
 */
final class FinancialTransactionCreateBatch extends TypedCollection
{
    private function __construct(FinancialTransactionCreate ...$items)
    {
        if (count($items) > 25) {
            throw new \OutOfRangeException('only 25 transactions allowed per request');
        }

        parent::__construct(...$items);
    }

    public static function fromTransactions(FinancialTransactionCreate ...$transactions): self
    {
        return new self(...$transactions);
    }
}


