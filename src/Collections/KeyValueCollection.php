<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections;

use Pirabyte\LaravelLexwareOffice\Dto\Common\KeyValue;

/**
 * @extends TypedCollection<KeyValue>
 */
final class KeyValueCollection extends TypedCollection
{
    private function __construct(KeyValue ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(KeyValue $pair): self
    {
        $items = $this->items();
        /** @var list<KeyValue> $items */
        $items[] = $pair;

        return new self(...$items);
    }

    public function getValue(string $key): ?string
    {
        foreach ($this as $pair) {
            /** @var KeyValue $pair */
            if ($pair->key === $key) {
                return $pair->value;
            }
        }

        return null;
    }
}


