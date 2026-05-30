<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Collections\KeyValueCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Common\KeyValue;
use Pirabyte\LaravelLexwareOffice\Dto\PartnerIntegrations\PartnerIntegration;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class PartnerIntegrationResourceTest extends TestCase
{
    /**
     * Loads fixture data from a JSON file.
     *
     * @param  string  $filename  The filename of the fixture (without path/extension)
     * @return array The decoded JSON data
     */
    private function loadFixture(string $filename): array
    {
        $path = __DIR__.'/../Fixtures/partner-integrations/'.$filename.'.json';
        if (! file_exists($path)) {
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
        $this->assertEquals('partner123', $partnerIntegration->partnerId);
        $this->assertEquals('customer456', $partnerIntegration->customerNumber);
        $this->assertEquals('ext789', $partnerIntegration->externalId);
        $this->assertEquals('value1', $partnerIntegration->data->getValue('additionalData1'));
        $this->assertEquals('value2', $partnerIntegration->data->getValue('additionalData2'));
    }

    public function test_it_can_update_partner_integration_data(): void
    {
        // First get the data
        $partnerIntegration = app('lexware-office')->partnerIntegrations()->get();

        $updatedIntegration = new PartnerIntegration(
            partnerId: $partnerIntegration->partnerId,
            customerNumber: $partnerIntegration->customerNumber,
            externalId: $partnerIntegration->externalId,
            data: KeyValueCollection::empty()
                ->with(new KeyValue('additionalData1', 'updatedValue1'))
                ->with(new KeyValue('additionalData2', 'updatedValue2')),
        );

        // Submit the update
        $updatedPartnerIntegration = app('lexware-office')->partnerIntegrations()->update($updatedIntegration);

        // Verify the updated data
        $this->assertInstanceOf(PartnerIntegration::class, $updatedPartnerIntegration);
        $this->assertEquals('partner123', $updatedPartnerIntegration->partnerId);
        $this->assertEquals('customer456', $updatedPartnerIntegration->customerNumber);
        $this->assertEquals('ext789', $updatedPartnerIntegration->externalId);
        $this->assertEquals('updatedValue1', $updatedPartnerIntegration->data->getValue('additionalData1'));
        $this->assertEquals('updatedValue2', $updatedPartnerIntegration->data->getValue('additionalData2'));
    }
}
