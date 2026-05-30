<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumber;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\PhoneNumberType;

/**
 * @extends TypedCollection<PhoneNumber>
 */
final class PhoneNumberCollection extends TypedCollection
{
    private function __construct(PhoneNumber ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(PhoneNumber $phone): self
    {
        $items = $this->items();
        /** @var list<PhoneNumber> $items */
        $items[] = $phone;

        return new self(...$items);
    }

    public function getByType(PhoneNumberType $type): ?PhoneNumber
    {
        foreach ($this as $phone) {
            /** @var PhoneNumber $phone */
            if ($phone->type === $type) {
                return $phone;
            }
        }

        return null;
    }
}


