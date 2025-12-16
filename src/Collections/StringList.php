<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable list of strings.
 *
 * This avoids array parameters/returns in the public API while still allowing
 * efficient iteration and membership checks.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class StringList implements IteratorAggregate, Countable
{
    /** @var list<string> */
    private array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }

    public static function fromIterable(iterable $values): self
    {
        $items = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $items[] = $value;
        }

        return new self(...$items);
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function contains(string $value): bool
    {
        return in_array($value, $this->values, true);
    }

    public function first(): ?string
    {
        return $this->values[0] ?? null;
    }
}


