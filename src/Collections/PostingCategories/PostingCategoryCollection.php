<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections\PostingCategories;

use Pirabyte\LaravelLexwareOffice\Collections\TypedCollection;
use Pirabyte\LaravelLexwareOffice\Dto\PostingCategories\PostingCategory;

/**
 * @extends TypedCollection<PostingCategory>
 */
final class PostingCategoryCollection extends TypedCollection
{
    private function __construct(PostingCategory ...$items)
    {
        parent::__construct(...$items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(PostingCategory $category): self
    {
        $items = $this->items();
        /** @var list<PostingCategory> $items */
        $items[] = $category;

        return new self(...$items);
    }
}


