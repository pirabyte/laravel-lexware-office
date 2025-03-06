<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum CreditCardProvider : string
{
    case VISA = 'VISA';
    case MASTERCARD = 'MASTERCARD';
    case AMEX = 'AMEX';
    case DINERSCLUB = 'DINERSCLUB';
}