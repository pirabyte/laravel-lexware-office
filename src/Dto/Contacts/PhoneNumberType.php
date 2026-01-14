<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Contacts;

enum PhoneNumberType: string
{
    case BUSINESS = 'business';
    case OFFICE = 'office';
    case MOBILE = 'mobile';
    case PRIVATE = 'private';
    case FAX = 'fax';
    case OTHER = 'other';
}


