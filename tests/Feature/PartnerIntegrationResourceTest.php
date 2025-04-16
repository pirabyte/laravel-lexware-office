<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Models\PartnerIntegration;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class PartnerIntegrationResourceTest extends TestCase
{
    /**
     * Loads fixture data from a JSON file.
     *
     * @param string $filename The filename of the fixture (without path/extension)
     * @return array The decoded JSON data
     */
    private function loadFixture(string $filename): array
    {
        $path = __DIR__.'/../Fixtures/partner-integrations/'.$filename.'.json';
        if (!file_exists($path)) {
            $this->fail('Fixture file not found: '.$path);
        }
        $content = file_get_contents($path);
        if ($content === false) {
            $this->fail('Could not read fixture file: '.$path);
        }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Error decoding JSON from fixture file: '.$path.' - '.json_last_error_msg());
        }

        return $data;
    }
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load fixture data for responses
        $getFixture = $this->loadFixture('1_partner_integration_response');
        $updateFixture = $this->loadFixture('2_updated_partner_integration_response');
        
        // Mock responses for the API calls
        $mockResponses = [
            // Response for GET
            new Response(200, ['Content-Type' => 'application/json'], json_encode($getFixture)),
            // Response for PUT
            new Response(200, ['Content-Type' => 'application/json'], json_encode($updateFixture)),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    public function test_it_can_get_partner_integration_data(): void
    {
        $partnerIntegration = app('lexware-office')->partnerIntegrations()->get();
        
        $this->assertInstanceOf(PartnerIntegration::class, $partnerIntegration);
        $this->assertEquals('partner123', $partnerIntegration->get('partnerId'));
        $this->assertEquals('customer456', $partnerIntegration->get('customerNumber'));
        $this->assertEquals('ext789', $partnerIntegration->get('externalId'));
        $this->assertEquals('value1', $partnerIntegration->get('data')['additionalData1']);
        $this->assertEquals('value2', $partnerIntegration->get('data')['additionalData2']);
    }

    public function test_it_can_update_partner_integration_data(): void
    {
        // First get the data
        $partnerIntegration = app('lexware-office')->partnerIntegrations()->get();
        
        // Update the data
        $partnerIntegration->set('data', [
            'additionalData1' => 'updatedValue1',
            'additionalData2' => 'updatedValue2'
        ]);
        
        // Submit the update
        $updatedPartnerIntegration = app('lexware-office')->partnerIntegrations()->update($partnerIntegration);
        
        // Verify the updated data
        $this->assertInstanceOf(PartnerIntegration::class, $updatedPartnerIntegration);
        $this->assertEquals('partner123', $updatedPartnerIntegration->get('partnerId'));
        $this->assertEquals('customer456', $updatedPartnerIntegration->get('customerNumber'));
        $this->assertEquals('ext789', $updatedPartnerIntegration->get('externalId'));
        $this->assertEquals('updatedValue1', $updatedPartnerIntegration->get('data')['additionalData1']);
        $this->assertEquals('updatedValue2', $updatedPartnerIntegration->get('data')['additionalData2']);
    }
}