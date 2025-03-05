<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use GuzzleHttp\Exception\GuzzleException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;

class ContactResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Erstellt einen neuen Kontakt
     *
     * @param array $data
     * @return array
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function create(array $data): array
    {
        return $this->client->post('contacts', $data);
    }

    /**
     * Ruft einen Kontakt anhand der ID ab
     *
     * @param string $id
     * @return array
     * @throws LexwareOfficeApiException
     */
    public function get(string $id): array
    {
        return $this->client->get("contacts/{$id}");
    }


}