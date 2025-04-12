<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Profile;

class ProfileResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Ruft die Profilinformationen ab
     *
     * @throws LexwareOfficeApiException
     */
    public function get(): Profile
    {
        $response = $this->client->get('profile');

        return Profile::fromArray($response);
    }
}
