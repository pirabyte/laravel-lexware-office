<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Pirabyte\LaravelLexwareOffice\Models\Profile;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ProfileResourceTest extends TestCase
{
    public function test_it_can_deserialize_profile_response(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/profile/1_profile_endpoint_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        $profile = Profile::fromArray($fixtureData);

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals($profile->getOrganizationId(), $fixtureData['organizationId']);
        $this->assertEquals($profile->getCompanyName(), $fixtureData['companyName']);
        $this->assertEquals($profile->getCreated(), $fixtureData['created']);
        $this->assertEquals($profile->getConnectionId(), $fixtureData['connectionId']);
        $this->assertEquals($profile->getFeatures(), $fixtureData['features']);
        $this->assertEquals($profile->getBusinessFeatures(), $fixtureData['businessFeatures']);
        $this->assertEquals($profile->getSubscriptionStatus(), $fixtureData['subscriptionStatus']);
        $this->assertEquals($profile->getTaxType(), $fixtureData['taxType']);
        $this->assertEquals($profile->isSmallBusiness(), $fixtureData['smallBusiness']);
    }
}