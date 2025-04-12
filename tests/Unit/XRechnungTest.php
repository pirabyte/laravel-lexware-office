<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Models\XRechnung;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class XRechnungTest extends TestCase
{
    /** @test */
    public function it_can_create_from_array()
    {
        $data = [
            'buyerReference' => 'BUYER-REF-123',
            'vendorNumberAtCustomer' => 'VENDOR-123',
        ];

        $xRechnung = XRechnung::fromArray($data);
        $serialized = $xRechnung->jsonSerialize();

        $this->assertEquals($data['buyerReference'], $serialized['buyerReference']);
        $this->assertEquals($data['vendorNumberAtCustomer'], $serialized['vendorNumberAtCustomer']);
    }

    /** @test */
    public function it_handles_optional_fields()
    {
        // Test with empty data
        $xRechnung = XRechnung::fromArray([]);
        $serialized = $xRechnung->jsonSerialize();

        $this->assertEmpty($serialized);

        // Test with only buyerReference
        $data = [
            'buyerReference' => 'BUYER-REF-456',
        ];

        $xRechnung = XRechnung::fromArray($data);
        $serialized = $xRechnung->jsonSerialize();

        $this->assertEquals($data['buyerReference'], $serialized['buyerReference']);
        $this->assertArrayNotHasKey('vendorNumberAtCustomer', $serialized);

        // Test with only vendorNumberAtCustomer
        $data = [
            'vendorNumberAtCustomer' => 'VENDOR-456',
        ];

        $xRechnung = XRechnung::fromArray($data);
        $serialized = $xRechnung->jsonSerialize();

        $this->assertEquals($data['vendorNumberAtCustomer'], $serialized['vendorNumberAtCustomer']);
        $this->assertArrayNotHasKey('buyerReference', $serialized);
    }

    /** @test */
    public function it_serializes_to_json_correctly()
    {
        $data = [
            'buyerReference' => 'BUYER-REF-789',
            'vendorNumberAtCustomer' => 'VENDOR-789',
        ];

        $xRechnung = XRechnung::fromArray($data);
        $json = json_encode($xRechnung);
        $decoded = json_decode($json, true);

        $this->assertEquals($data['buyerReference'], $decoded['buyerReference']);
        $this->assertEquals($data['vendorNumberAtCustomer'], $decoded['vendorNumberAtCustomer']);
    }
}
