<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Profile;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\StringList;
use Pirabyte\LaravelLexwareOffice\Dto\Profile\Profile;
use Pirabyte\LaravelLexwareOffice\Dto\Profile\ProfileCreated;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class ProfileMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): Profile
    {
        $data = JsonCodec::decode($rawJson);

        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for Profile', $rawJson);
        }

        /** @var array<string, mixed> $data */
        $createdData = Assert::array($data['created'] ?? null, 'Profile.created must be an object');
        if (array_is_list($createdData)) {
            throw new DecodeException('Profile.created must be an object', $rawJson);
        }

        try {
            $createdDate = new DateTimeImmutable(Assert::string($createdData['date'] ?? null, 'Profile.created.date missing'));
        } catch (\Throwable $e) {
            throw new DecodeException('Invalid Profile.created.date datetime', $rawJson, $e);
        }

        $created = new ProfileCreated(
            userId: Assert::string($createdData['userId'] ?? null, 'Profile.created.userId missing'),
            userName: Assert::string($createdData['userName'] ?? null, 'Profile.created.userName missing'),
            userEmail: Assert::string($createdData['userEmail'] ?? null, 'Profile.created.userEmail missing'),
            date: $createdDate,
        );

        return new Profile(
            organizationId: Assert::string($data['organizationId'] ?? null, 'Profile.organizationId missing'),
            companyName: Assert::string($data['companyName'] ?? null, 'Profile.companyName missing'),
            created: $created,
            connectionId: Assert::string($data['connectionId'] ?? null, 'Profile.connectionId missing'),
            features: StringList::fromIterable(Assert::array($data['features'] ?? [], 'Profile.features must be a list')),
            businessFeatures: StringList::fromIterable(Assert::array($data['businessFeatures'] ?? [], 'Profile.businessFeatures must be a list')),
            subscriptionStatus: Assert::string($data['subscriptionStatus'] ?? null, 'Profile.subscriptionStatus missing'),
            taxType: Assert::string($data['taxType'] ?? null, 'Profile.taxType missing'),
            smallBusiness: Assert::bool($data['smallBusiness'] ?? null, 'Profile.smallBusiness missing'),
        );
    }
}


