<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;

/**
 * @extends TypedCollection<MultipartPart>
 */
final class MultipartBody extends TypedCollection
{
    private function __construct(MultipartPart ...$parts)
    {
        parent::__construct(...$parts);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function withPart(MultipartPart $part): self
    {
        $items = $this->items();
        /** @var list<MultipartPart> $items */
        $items[] = $part;

        return new self(...$items);
    }
}


