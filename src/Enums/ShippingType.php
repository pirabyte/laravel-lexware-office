<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum ShippingType: string
{
    case SERVICE = 'service';
    case SERVICE_PERIOD = 'serviceperiod';
    case DELIVERY = 'delivery';
    case DELIVERY_PERIOD = 'deliveryperiod';
    case NONE = 'none';
}
