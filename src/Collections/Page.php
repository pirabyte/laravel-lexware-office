<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections;

/**
 * @template T
 */
final readonly class Page
{
    /**
     * @param  TypedCollection<T>  $items
     */
    public function __construct(
        public TypedCollection $items,
        public PageInfo $pageInfo,
    ) {}
}


