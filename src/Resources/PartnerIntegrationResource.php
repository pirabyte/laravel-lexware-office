<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\PartnerIntegration;

class PartnerIntegrationResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieves partner integration data for a lexoffice organization
     *
     * @throws LexwareOfficeApiException
     */
    public function get(): PartnerIntegration
    {
        $response = $this->client->get('partner-integrations');

        return PartnerIntegration::fromArray($response);
    }

    /**
     * Updates partner integration data for a lexoffice organization
     *
     * @param  PartnerIntegration  $partnerIntegration  The partner integration data to update
     *
     * @throws LexwareOfficeApiException
     */
    public function update(PartnerIntegration $partnerIntegration): PartnerIntegration
    {
        $response = $this->client->put('partner-integrations', $partnerIntegration->toArray());

        return PartnerIntegration::fromArray($response);
    }
}
