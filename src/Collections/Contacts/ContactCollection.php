<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Contact;

/**
 * @extends TypedCollection<Contact>
 */
final class ContactCollection extends TypedCollection
{
    private function __construct(Contact ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(Contact $contact): self
    {
        $items = $this->items();
        /** @var list<Contact> $items */
        $items[] = $contact;

        return new self(...$items);
    }
}


