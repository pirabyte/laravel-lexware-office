<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum PostingCategoryType: string
{
    case ALL = '';
    case INCOME = 'income';
    case EXPENSE = 'outgo';
}
