<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\PartnerIntegrations;

use Pirabyte\LaravelLexwareOffice\Collections\KeyValueCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class PartnerIntegration implements Dto
{
    public function __construct(
        public string $partnerId,
        public string $customerNumber,
        public string $externalId,
        public KeyValueCollection $data,
    ) {
        Assert::nonEmptyString($this->partnerId, 'PartnerIntegration.partnerId must be non-empty');
        Assert::nonEmptyString($this->customerNumber, 'PartnerIntegration.customerNumber must be non-empty');
        Assert::nonEmptyString($this->externalId, 'PartnerIntegration.externalId must be non-empty');
    }
}


