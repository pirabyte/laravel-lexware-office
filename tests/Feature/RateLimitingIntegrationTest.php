<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Pirabyte\LaravelLexwareOffice\Classes\LexwareRateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class RateLimitingIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_legacy_rate_limiting_still_works()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['data' => 'test'])),
        ]);
        
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key',
            'test-rate-limit-key',
            50
        );
        
        $lexwareOffice->setClient($client);
        
        // Should work with legacy rate limiting
        $result = $lexwareOffice->get('contacts');
        $this->assertEquals(['data' => 'test'], $result);
    }

    public function test_advanced_rate_limiting_prevents_excessive_requests()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['data' => 'test1'])),
            new Response(200, [], json_encode(['data' => 'test2'])),
            new Response(200, [], json_encode(['data' => 'test3'])),
            new Response(200, [], json_encode(['data' => 'test4'])),
            new Response(200, [], json_encode(['data' => 'test5'])),
        ]);
        
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key'
        );
        
        $lexwareOffice->setClient($client);
        $lexwareOffice->setAdvancedRateLimiter($rateLimiter);
        $lexwareOffice->useAdvancedRateLimiting(true);
        
        // First 5 requests should work (burst size)
        for ($i = 1; $i <= 5; $i++) {
            $result = $lexwareOffice->get('contacts');
            $this->assertEquals(['data' => "test$i"], $result);
        }
        
        // 6th request should be rate limited
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(LexwareOfficeApiException::STATUS_RATE_LIMITED);
        
        $lexwareOffice->get('contacts');
    }

    public function test_different_endpoints_have_separate_limits()
    {
        $mockHandler = new MockHandler(array_fill(0, 10, new Response(200, [], json_encode(['data' => 'test']))));
        
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key'
        );
        
        $lexwareOffice->setClient($client);
        $lexwareOffice->setAdvancedRateLimiter($rateLimiter);
        $lexwareOffice->useAdvancedRateLimiting(true);
        
        // Exhaust limit for contacts endpoint
        for ($i = 0; $i < 5; $i++) {
            $result = $lexwareOffice->get('contacts');
            $this->assertEquals(['data' => 'test'], $result);
        }
        
        // contacts should be rate limited
        try {
            $lexwareOffice->get('contacts');
            $this->fail('Expected rate limit exception for contacts');
        } catch (LexwareOfficeApiException $e) {
            $this->assertEquals(LexwareOfficeApiException::STATUS_RATE_LIMITED, $e->getCode());
        }
        
        // But vouchers should still work
        $result = $lexwareOffice->get('vouchers');
        $this->assertEquals(['data' => 'test'], $result);
    }

    public function test_rate_limiter_status_can_be_retrieved()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key'
        );
        
        $lexwareOffice->setAdvancedRateLimiter($rateLimiter);
        $lexwareOffice->useAdvancedRateLimiting(true);
        
        $status = $lexwareOffice->getRateLimiterStatus('contacts');
        
        $this->assertArrayHasKey('connection', $status);
        $this->assertArrayHasKey('client', $status);
        
        $this->assertEquals(5, $status['connection']['tokens']);
        $this->assertEquals(2, $status['connection']['limit']);
        $this->assertEquals(5, $status['connection']['burst']);
        
        $this->assertEquals(5, $status['client']['tokens']);
        $this->assertEquals(5, $status['client']['limit']);
        $this->assertEquals(5, $status['client']['burst']);
    }

    public function test_legacy_rate_limiter_status()
    {
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key',
            'test-rate-key',
            50
        );
        
        // Should fall back to legacy status
        $status = $lexwareOffice->getRateLimiterStatus('contacts');
        
        $this->assertArrayHasKey('legacy', $status);
        $this->assertEquals('test-rate-key', $status['legacy']['key']);
        $this->assertEquals(50, $status['legacy']['max_per_minute']);
    }

    public function test_rate_limit_error_includes_proper_retry_information()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        $lexwareOffice = new LexwareOffice(
            'https://api.lexoffice.io/v1',
            'test-api-key'
        );
        
        $lexwareOffice->setAdvancedRateLimiter($rateLimiter);
        $lexwareOffice->useAdvancedRateLimiting(true);
        
        // Exhaust the rate limit
        for ($i = 0; $i < 5; $i++) {
            $mockHandler = new MockHandler([new Response(200, [], json_encode(['data' => 'test']))]);
            $handlerStack = HandlerStack::create($mockHandler);
            $client = new Client(['handler' => $handlerStack]);
            $lexwareOffice->setClient($client);
            
            $lexwareOffice->get('contacts');
        }
        
        // Next request should be rate limited with proper error information
        try {
            $mockHandler = new MockHandler([new Response(200, [], json_encode(['data' => 'test']))]);
            $handlerStack = HandlerStack::create($mockHandler);
            $client = new Client(['handler' => $handlerStack]);
            $lexwareOffice->setClient($client);
            
            $lexwareOffice->get('contacts');
            $this->fail('Expected rate limit exception');
        } catch (LexwareOfficeApiException $e) {
            $errorData = json_decode($e->getMessage(), true);
            
            $this->assertEquals('Rate limit exceeded', $errorData['message']);
            $this->assertArrayHasKey('retryAfter', $errorData);
            $this->assertArrayHasKey('limitType', $errorData);
            $this->assertEquals('connection', $errorData['limitType']);
            $this->assertGreaterThan(0, $errorData['retryAfter']);
        }
    }
}