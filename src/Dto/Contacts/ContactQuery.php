<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class ContactQuery implements Dto
{
    public function __construct(
        public ?bool $customer = null,
        public ?bool $vendor = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $number = null,
        public int $page = 0,
        public int $size = 25,
    ) {
        Assert::intRange($this->page, 0, PHP_INT_MAX, 'ContactQuery.page must be >= 0');
        Assert::intRange($this->size, 1, 250, 'ContactQuery.size must be between 1 and 250');
    }
}


