<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Finance;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\VoucherAssignment;

/**
 * @extends TypedCollection<VoucherAssignment>
 */
final class VoucherAssignmentCollection extends TypedCollection
{
    private function __construct(VoucherAssignment ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(VoucherAssignment $assignment): self
    {
        $items = $this->items();
        /** @var list<VoucherAssignment> $items */
        $items[] = $assignment;

        return new self(...$items);
    }
}


