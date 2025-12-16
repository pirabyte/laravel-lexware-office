<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Collections;

use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class PageInfo
{
    public function __construct(
        public int $page,
        public int $size,
        public bool $first,
        public bool $last,
        public int $totalPages,
        public int $totalElements,
        public int $numberOfElements,
    ) {
        Assert::intRange($this->page, 0, PHP_INT_MAX, 'page must be >= 0');
        Assert::intRange($this->size, 0, PHP_INT_MAX, 'size must be >= 0');
        Assert::intRange($this->totalPages, 0, PHP_INT_MAX, 'totalPages must be >= 0');
        Assert::intRange($this->totalElements, 0, PHP_INT_MAX, 'totalElements must be >= 0');
        Assert::intRange($this->numberOfElements, 0, PHP_INT_MAX, 'numberOfElements must be >= 0');
    }
}


