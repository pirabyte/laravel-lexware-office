<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddress;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\EmailAddressType;

/**
 * @extends TypedCollection<EmailAddress>
 */
final class EmailAddressCollection extends TypedCollection
{
    private function __construct(EmailAddress ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(EmailAddress $email): self
    {
        $items = $this->items();
        /** @var list<EmailAddress> $items */
        $items[] = $email;

        return new self(...$items);
    }

    public function getByType(EmailAddressType $type): ?EmailAddress
    {
        foreach ($this as $email) {
            /** @var EmailAddress $email */
            if ($email->type === $type) {
                return $email;
            }
        }

        return null;
    }
}


