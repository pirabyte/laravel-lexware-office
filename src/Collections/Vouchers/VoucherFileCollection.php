<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Vouchers;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherFile;

/**
 * @extends TypedCollection<VoucherFile>
 */
final class VoucherFileCollection extends TypedCollection
{
    private function __construct(VoucherFile ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(VoucherFile $file): self
    {
        $items = $this->items();
        /** @var list<VoucherFile> $items */
        $items[] = $file;

        return new self(...$items);
    }
}


