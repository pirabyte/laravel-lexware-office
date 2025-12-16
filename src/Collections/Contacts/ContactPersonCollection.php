<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactPerson;

/**
 * @extends TypedCollection<ContactPerson>
 */
final class ContactPersonCollection extends TypedCollection
{
    private function __construct(ContactPerson ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(ContactPerson $person): self
    {
        $items = $this->items();
        /** @var list<ContactPerson> $items */
        $items[] = $person;

        return new self(...$items);
    }
}


