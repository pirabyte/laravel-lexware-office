<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Vouchers;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherItem;

/**
 * @extends TypedCollection<VoucherItem>
 */
final class VoucherItemCollection extends TypedCollection
{
    private function __construct(VoucherItem ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(VoucherItem $item): self
    {
        $items = $this->items();
        /** @var list<VoucherItem> $items */
        $items[] = $item;

        return new self(...$items);
    }
}


