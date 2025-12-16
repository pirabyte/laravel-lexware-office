<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Profile;

use Pirabyte\LaravelLexwareOffice\Collections\StringList;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class Profile implements Dto
{
    public function __construct(
        public string $organizationId,
        public string $companyName,
        public ProfileCreated $created,
        public string $connectionId,
        public StringList $features,
        public StringList $businessFeatures,
        public string $subscriptionStatus,
        public string $taxType,
        public bool $smallBusiness,
    ) {
        Assert::nonEmptyString($this->organizationId, 'Profile.organizationId must be non-empty');
        Assert::nonEmptyString($this->companyName, 'Profile.companyName must be non-empty');
        Assert::nonEmptyString($this->connectionId, 'Profile.connectionId must be non-empty');
        Assert::nonEmptyString($this->subscriptionStatus, 'Profile.subscriptionStatus must be non-empty');
        Assert::nonEmptyString($this->taxType, 'Profile.taxType must be non-empty');
    }
}


