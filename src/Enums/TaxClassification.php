<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum TaxClassification : string
{
    case GERMANY = 'de';
    case INTRA_COMMUNITY = 'intraCommunity';
    case THIRD_PARTY_COUNTRY = 'thirdPartyCountry';
}