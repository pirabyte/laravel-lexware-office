<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\Countries;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Countries\Country;

/**
 * @extends TypedCollection<Country>
 */
final class CountryCollection extends TypedCollection
{
    private function __construct(Country ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(Country $country): self
    {
        $items = $this->items();
        /** @var list<Country> $items */
        $items[] = $country;

        return new self(...$items);
    }
}


