<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum AccountSystem: string
{
    // Hier können weitere Werte hinzugefügt werden, wenn bekannt.
    // Laut Dokumentation: "Please contact us to get a value for usage."
    case UNKNOWN = 'UNKNOWN';
    case PAYMENT_PROVIDER = 'PaymentProvider';
}
