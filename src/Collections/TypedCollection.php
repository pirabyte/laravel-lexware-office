<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template T of object
 * @implements IteratorAggregate<int, T>
 */
abstract class TypedCollection implements IteratorAggregate, Countable
{
    /** @var list<T> */
    private array $items;

    /**
     * @param  T  ...$items
     */
    protected function __construct(object ...$items)
    {
        $this->items = $items;
    }

    /**
     * @return list<T>
     */
    final protected function items(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<int, T>
     */
    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    final public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return T|null
     */
    final public function first(): ?object
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    final public function get(int $index): ?object
    {
        return $this->items[$index] ?? null;
    }
}


