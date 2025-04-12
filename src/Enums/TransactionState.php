<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum TransactionState: string
{
    case COMPLETELY_ASSIGNED = 'completely_assigned';
    case PARTLY_ASSIGNED = 'partly_assigned';
    case INVISIBLE = 'invisible';
}
