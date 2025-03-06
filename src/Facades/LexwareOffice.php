<?php

namespace Pirabyte\LaravelLexwareOffice\Facades;

use Illuminate\Support\Facades\Facade;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\PostingCategoryResource;
use Pirabyte\LaravelLexwareOffice\Resources\ProfileResource;
use Pirabyte\LaravelLexwareOffice\Resources\VoucherResource;

/**
 * @method static ContactResource contacts()
 * @method static VoucherResource vouchers()
 * @method static ProfileResource profile()
 * @method static PostingCategoryResource postingCategories()
 */
class LexwareOffice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'lexware-office';
    }
}