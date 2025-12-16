<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Dto\Profile\Profile;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Mappers\Profile\ProfileMapper;

class ProfileResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function get(): Profile
    {
        $response = $this->http->get('profile');

        return ProfileMapper::fromJson($response->body);
    }
}
