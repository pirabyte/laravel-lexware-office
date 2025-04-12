<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum FinancialAccountType: string
{
    case GIRO = 'GIRO';
    case SAVINGS = 'SAVINGS';
    case FIXEDTERM = 'FIXEDTERM';
    case SECURITIES = 'SECURITIES';
    case LOAN = 'LOAN';
    case CREDITCARD = 'CREDITCARD';
    case CASH = 'CASH';
    case PAYMENT_PROVIDER = 'PAYMENT_PROVIDER';
}
