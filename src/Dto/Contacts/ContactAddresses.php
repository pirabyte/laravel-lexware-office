<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

use Pirabyte\LaravelLexwareOffice\Dto\Common\Address;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;

final readonly class ContactAddresses implements Dto
{
    public function __construct(
        public ?Address $billing,
        public ?Address $shipping,
    ) {}
}


