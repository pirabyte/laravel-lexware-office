<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

enum OAuthErrorCategory: string
{
    case InvalidGrant = 'invalid_grant';
    case Transient = 'transient';
    case OutcomeUnknown = 'outcome_unknown';
    case ClientConfiguration = 'client_configuration';
}
