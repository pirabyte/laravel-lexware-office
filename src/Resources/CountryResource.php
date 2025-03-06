<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Country;

class CountryResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Ruft alle verfügbaren Länder ab
     *
     * @return array Eine Liste aller Länder
     * @throws LexwareOfficeApiException
     */
    public function all(): array
    {
        $response = $this->client->get('countries');
        return $this->processCountriesResponse($response);
    }

    /**
     * Verarbeitet die Antwort der Countries-API und erstellt daraus ein strukturiertes Array
     *
     * @param array $response API-Antwort
     * @return array Ein Array mit Country-Objekten
     */
    protected function processCountriesResponse(array $response): array
    {
        $countries = [];

        foreach ($response as $countryData) {
            $countries[] = Country::fromArray($countryData);
        }

        return $countries;
    }
}