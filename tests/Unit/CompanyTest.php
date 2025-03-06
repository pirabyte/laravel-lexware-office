<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Models\Company;
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
                ['id' => '1', 'name' => 'John Doe'],
                ['id' => '2', 'name' => 'Jane Doe']
            ]
        ];

        $company = Company::fromArray($data);
        $serialized = $company->jsonSerialize();

        $this->assertEquals($data['name'], $serialized['name']);
        $this->assertEquals($data['taxNumber'], $serialized['taxNumber']);
        $this->assertEquals($data['vatRegistrationId'], $serialized['vatRegistrationId']);
        $this->assertEquals($data['allowTaxFreeInvoices'], $serialized['allowTaxFreeInvoices']);
        $this->assertEquals($data['contactPersons'], $serialized['contactPersons']);
    }

    /** @test */
    public function it_handles_optional_fields()
    {
        // Test with only required field (name)
        $data = [
            'name' => 'Minimal Company'
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
                ['id' => '3', 'name' => 'Max Mustermann']
            ]
        ];

        $company = Company::fromArray($data);
        $json = json_encode($company);
        $decoded = json_decode($json, true);

        $this->assertEquals($data['name'], $decoded['name']);
        $this->assertEquals($data['taxNumber'], $decoded['taxNumber']);
        $this->assertEquals($data['vatRegistrationId'], $decoded['vatRegistrationId']);
        $this->assertEquals($data['allowTaxFreeInvoices'], $decoded['allowTaxFreeInvoices']);
        $this->assertEquals($data['contactPersons'], $decoded['contactPersons']);
    }
}