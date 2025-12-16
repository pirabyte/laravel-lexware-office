<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Finance;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccount;

/**
 * @extends TypedCollection<FinancialAccount>
 */
final class FinancialAccountCollection extends TypedCollection
{
    private function __construct(FinancialAccount ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(FinancialAccount $account): self
    {
        $items = $this->items();
        /** @var list<FinancialAccount> $items */
        $items[] = $account;

        return new self(...$items);
    }
}


