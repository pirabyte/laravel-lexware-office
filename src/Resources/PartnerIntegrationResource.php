<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Dto\PartnerIntegrations\PartnerIntegration;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Mappers\PartnerIntegrations\PartnerIntegrationMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\PartnerIntegrations\PartnerIntegrationWriteMapper;

class PartnerIntegrationResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function get(): PartnerIntegration
    {
        $response = $this->http->get('partner-integrations');

        return PartnerIntegrationMapper::fromJson($response->body);
    }

    public function update(PartnerIntegration $partnerIntegration): PartnerIntegration
    {
        $body = PartnerIntegrationWriteMapper::toJsonBody($partnerIntegration);
        $response = $this->http->putJson('partner-integrations', $body);

        return PartnerIntegrationMapper::fromJson($response->body);
    }
}
