<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class BackwardCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up configuration to use legacy rate limiting for this test
        config(['lexware-office.rate_limiting.enabled' => false]);
    }

    public function test_legacy_rate_limiting_still_works()
    {
        // Mock API response
        $mockResponses = [
            new Response(200, [
                'Content-Type' => 'application/json'
            ], json_encode([
                'content' => [
                    [
                        'id' => 'test-contact-id',
                        'organizationName' => 'Test Organization',
                        'version' => 1
                    ]
                ]
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);
        
        // Ensure we're using legacy rate limiting
        $instance->useAdvancedRateLimiting(false);

        // Test that the request works
        $result = LexwareOffice::contacts()->getAll();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertCount(1, $result['content']);
        $this->assertEquals('test-contact-id', $result['content'][0]['id']);
    }

    public function test_facade_still_works_with_existing_methods()
    {
        // Mock API response for profile
        $mockResponses = [
            new Response(200, [
                'Content-Type' => 'application/json'
            ], json_encode([
                'organizationId' => 'test-org-id',
                'companyName' => 'Test Company'
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);
        $instance->useAdvancedRateLimiting(false);

        // Test that profile endpoint still works
        $result = LexwareOffice::profile()->get();
        
        $this->assertIsArray($result);
        $this->assertEquals('test-org-id', $result['organizationId']);
        $this->assertEquals('Test Company', $result['companyName']);
    }

    public function test_service_provider_configures_instance_correctly()
    {
        $instance = app('lexware-office');
        
        // Check that instance is properly configured
        $this->assertNotNull($instance);
        $this->assertInstanceOf(\Pirabyte\LaravelLexwareOffice\LexwareOffice::class, $instance);
        
        // Check that contacts resource is available
        $this->assertInstanceOf(\Pirabyte\LaravelLexwareOffice\Resources\ContactResource::class, $instance->contacts());
        
        // Check that vouchers resource is available
        $this->assertInstanceOf(\Pirabyte\LaravelLexwareOffice\Resources\VoucherResource::class, $instance->vouchers());
    }

    public function test_rate_limiter_can_be_disabled()
    {
        $instance = app('lexware-office');
        
        // Disable advanced rate limiting
        $instance->useAdvancedRateLimiting(false);
        
        // Test that legacy status is returned
        $status = $instance->getRateLimiterStatus();
        
        $this->assertArrayHasKey('legacy', $status);
        $this->assertArrayHasKey('key', $status['legacy']);
        $this->assertArrayHasKey('max_per_minute', $status['legacy']);
    }
}