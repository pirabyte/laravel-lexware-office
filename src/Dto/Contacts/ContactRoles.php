<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Dto;

final readonly class ContactRoles implements Dto
{
    public function __construct(
        public ?CustomerRole $customer,
        public ?VendorRole $vendor,
    ) {}
}


