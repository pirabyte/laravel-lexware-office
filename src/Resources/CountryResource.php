<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Collections\Countries\CountryCollection;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Mappers\Countries\CountryMapper;

class CountryResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function all(): CountryCollection
    {
        $response = $this->http->get('countries');

        return CountryMapper::collectionFromJson($response->body);
    }
}
