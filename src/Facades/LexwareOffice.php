<?php

namespace Pirabyte\LaravelLexwareOffice\Facades;

use Illuminate\Support\Facades\Facade;

class LexwareOffice extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'lexware-office';
    }
}