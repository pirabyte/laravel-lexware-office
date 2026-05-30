<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Pirabyte\LaravelLexwareOffice\Dto\Profile\Profile;
use Pirabyte\LaravelLexwareOffice\Mappers\Profile\ProfileMapper;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ProfileResourceTest extends TestCase
{
    public function test_it_can_deserialize_profile_response(): void
    {
        $fixtureFile = __DIR__.'/../Fixtures/profile/1_profile_endpoint_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        $profile = ProfileMapper::fromJson($fixtureContents);

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals($fixtureData['organizationId'], $profile->organizationId);
        $this->assertEquals($fixtureData['companyName'], $profile->companyName);
        $this->assertEquals($fixtureData['created']['userId'], $profile->created->userId);
        $this->assertEquals($fixtureData['created']['userName'], $profile->created->userName);
        $this->assertEquals($fixtureData['created']['userEmail'], $profile->created->userEmail);
        $this->assertEquals((new \DateTimeImmutable($fixtureData['created']['date']))->format('c'), $profile->created->date->format('c'));
        $this->assertEquals($fixtureData['connectionId'], $profile->connectionId);
        $this->assertEquals($fixtureData['features'], iterator_to_array($profile->features));
        $this->assertEquals($fixtureData['businessFeatures'], iterator_to_array($profile->businessFeatures));
        $this->assertEquals($fixtureData['subscriptionStatus'], $profile->subscriptionStatus);
        $this->assertEquals($fixtureData['taxType'], $profile->taxType);
        $this->assertEquals($fixtureData['smallBusiness'], $profile->smallBusiness);
    }
}
