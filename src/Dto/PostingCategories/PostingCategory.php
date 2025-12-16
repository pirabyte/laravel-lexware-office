<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\PostingCategories;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class PostingCategory implements Dto
{
    public function __construct(
        public string $id,
        public string $name,
        public PostingCategoryType $type,
        public bool $contactRequired,
        public bool $splitAllowed,
        public ?string $groupName,
    ) {
        Assert::nonEmptyString($this->id, 'PostingCategory.id must be non-empty');
        Assert::nonEmptyString($this->name, 'PostingCategory.name must be non-empty');
    }
}


