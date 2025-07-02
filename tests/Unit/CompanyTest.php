<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Models\Company;
use Pirabyte\LaravelLexwareOffice\Models\ContactPerson;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class CompanyTest extends TestCase
{
    /** @test */
    public function it_can_create_from_array()
    {
        $data = [
            'name' => 'Test Company GmbH',
            'taxNumber' => '123456789',
            'vatRegistrationId' => 'DE123456789',
            'allowTaxFreeInvoices' => true,
            'contactPersons' => [
                ['salutation' => 'Mr.', 'firstName' => 'John', 'lastName' => 'Doe', 'primary' => true, 'emailAddress' => 'john.doe@example.com', 'phoneNumber' => '12345'],
                ['salutation' => 'Ms.', 'firstName' => 'Jane', 'lastName' => 'Doe', 'primary' => false, 'emailAddress' => 'jane.doe@example.com', 'phoneNumber' => '67890'],
            ],
        ];

        $company = Company::fromArray($data);

        $this->assertEquals($data['name'], $company->getName());
        $this->assertEquals($data['taxNumber'], $company->getTaxNumber());
        $this->assertEquals($data['vatRegistrationId'], $company->getVatRegistrationId());
        $this->assertEquals($data['allowTaxFreeInvoices'], $company->getAllowTaxFreeInvoices());

        $contactPersons = $company->getContactPersons();
        $this->assertCount(2, $contactPersons);
        $this->assertInstanceOf(ContactPerson::class, $contactPersons[0]);
        $this->assertEquals('John', $contactPersons[0]->getFirstName());
        $this->assertEquals('Jane', $contactPersons[1]->getFirstName());
    }

    /** @test */
    public function it_handles_optional_fields()
    {
        // Test with only required field (name)
        $data = [
            'name' => 'Minimal Company',
        ];

        $company = Company::fromArray($data);
        $serialized = $company->jsonSerialize();

        $this->assertEquals($data['name'], $serialized['name']);
        $this->assertArrayNotHasKey('taxNumber', $serialized);
        $this->assertArrayNotHasKey('vatRegistrationId', $serialized);
        $this->assertArrayNotHasKey('allowTaxFreeInvoices', $serialized);
        $this->assertArrayNotHasKey('contactPersons', $serialized);
    }

    /** @test */
    public function it_serializes_to_json_correctly()
    {
        $data = [
            'name' => 'JSON Company',
            'taxNumber' => '987654321',
            'vatRegistrationId' => 'DE987654321',
            'allowTaxFreeInvoices' => true,
            'contactPersons' => [
                ['salutation' => 'Mr.', 'firstName' => 'Max', 'lastName' => 'Mustermann', 'primary' => true, 'emailAddress' => 'max@example.com', 'phoneNumber' => '12345'],
            ],
        ];

        $company = Company::fromArray($data);
        $json = json_encode($company);
        $decoded = json_decode($json, true);

        $this->assertEquals($data['name'], $decoded['name']);
        $this->assertEquals($data['taxNumber'], $decoded['taxNumber']);
        $this->assertEquals($data['vatRegistrationId'], $decoded['vatRegistrationId']);
        $this->assertEquals($data['allowTaxFreeInvoices'], $decoded['allowTaxFreeInvoices']);

        // Manually compare contactPersons as they are now objects
        $this->assertCount(1, $decoded['contactPersons']);
        $this->assertEquals('Max', $decoded['contactPersons'][0]['firstName']);
        $this->assertEquals('max@example.com', $decoded['contactPersons'][0]['emailAddress']);
    }
}
