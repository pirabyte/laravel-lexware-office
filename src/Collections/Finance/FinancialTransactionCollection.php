<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Finance;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransaction;

/**
 * @extends TypedCollection<FinancialTransaction>
 */
final class FinancialTransactionCollection extends TypedCollection
{
    private function __construct(FinancialTransaction ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(FinancialTransaction $transaction): self
    {
        $items = $this->items();
        /** @var list<FinancialTransaction> $items */
        $items[] = $transaction;

        return new self(...$items);
    }
}


