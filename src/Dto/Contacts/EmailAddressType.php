<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

enum EmailAddressType: string
{
    case BUSINESS = 'business';
    case OFFICE = 'office';
    case PRIVATE = 'private';
    case OTHER = 'other';
}


