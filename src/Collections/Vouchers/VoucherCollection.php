<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Vouchers;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\Voucher;

/**
 * @extends TypedCollection<Voucher>
 */
final class VoucherCollection extends TypedCollection
{
    private function __construct(Voucher ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(Voucher $voucher): self
    {
        $items = $this->items();
        /** @var list<Voucher> $items */
        $items[] = $voucher;

        return new self(...$items);
    }
}


