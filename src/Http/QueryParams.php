<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Http;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;

/**
 * @extends TypedCollection<QueryParam>
 */
final class QueryParams extends TypedCollection
{
    private function __construct(QueryParam ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(string $key, string|int|bool $value): self
    {
        $items = $this->items();
        /** @var list<QueryParam> $items */
        $items[] = new QueryParam($key, self::stringify($value));

        return new self(...$items);
    }

    public function withMany(string $key, string|int|bool ...$values): self
    {
        $items = $this->items();
        /** @var list<QueryParam> $items */
        foreach ($values as $value) {
            $items[] = new QueryParam($key, self::stringify($value));
        }

        return new self(...$items);
    }

    private static function stringify(string|int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}


