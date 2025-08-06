<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Classes\LexwareRateLimiter;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class LexwareRateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        parent::tearDown();
    }

    public function test_allows_initial_requests_up_to_burst_size()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        
        // Should allow up to 5 requests immediately for connection limit (burst size)
        for ($i = 0; $i < 5; $i++) {
            $result = $rateLimiter->isAllowed('contacts');
            $this->assertTrue($result['allowed'], "Request $i should be allowed");
            if ($result['allowed']) {
                $rateLimiter->recordHit('contacts');
            }
        }
        
        // 6th request should be blocked for connection limit (2 requests/s, burst 5)
        $result = $rateLimiter->isAllowed('contacts');
        $this->assertFalse($result['allowed'], 'Request after burst should be blocked');
        $this->assertEquals('connection', $result['limitType']);
    }

    public function test_connection_and_client_limits_are_separate()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        
        // Exhaust connection limit (2 req/s, burst 5)
        for ($i = 0; $i < 5; $i++) {
            $result = $rateLimiter->isAllowed('contacts');
            $this->assertTrue($result['allowed']);
            $rateLimiter->recordHit('contacts');
        }
        
        // Next request should fail on connection limit
        $result = $rateLimiter->isAllowed('contacts');
        $this->assertFalse($result['allowed']);
        $this->assertEquals('connection', $result['limitType']);
    }

    public function test_endpoint_normalization()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        
        // Test that different endpoints are treated separately
        $result1 = $rateLimiter->isAllowed('contacts');
        $this->assertTrue($result1['allowed']);
        $rateLimiter->recordHit('contacts');
        
        $result2 = $rateLimiter->isAllowed('vouchers');
        $this->assertTrue($result2['allowed']);
        $rateLimiter->recordHit('vouchers');
        
        // Test that UUID and numeric IDs are normalized
        $result3 = $rateLimiter->isAllowed('contacts/12345');
        $result4 = $rateLimiter->isAllowed('contacts/67890');
        
        // These should be treated as the same endpoint due to ID normalization
        $this->assertTrue($result3['allowed']);
        $rateLimiter->recordHit('contacts/12345');
        
        $this->assertTrue($result4['allowed']);
        $rateLimiter->recordHit('contacts/67890');
    }

    public function test_rate_limiter_status_reporting()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        
        $status = $rateLimiter->getStatus('contacts');
        
        $this->assertArrayHasKey('connection', $status);
        $this->assertArrayHasKey('client', $status);
        
        $this->assertEquals(LexwareRateLimiter::CONNECTION_BURST_SIZE, $status['connection']['tokens']);
        $this->assertEquals(LexwareRateLimiter::CONNECTION_RATE_LIMIT, $status['connection']['limit']);
        $this->assertEquals(LexwareRateLimiter::CONNECTION_BURST_SIZE, $status['connection']['burst']);
        
        $this->assertEquals(LexwareRateLimiter::CLIENT_BURST_SIZE, $status['client']['tokens']);
        $this->assertEquals(LexwareRateLimiter::CLIENT_RATE_LIMIT, $status['client']['limit']);
        $this->assertEquals(LexwareRateLimiter::CLIENT_BURST_SIZE, $status['client']['burst']);
    }

    public function test_different_connections_have_separate_limits()
    {
        $rateLimiter1 = new LexwareRateLimiter('connection-1', 'client-1');
        $rateLimiter2 = new LexwareRateLimiter('connection-2', 'client-1');
        
        // Exhaust limit for connection-1
        for ($i = 0; $i < 5; $i++) {
            $result = $rateLimiter1->isAllowed('contacts');
            $this->assertTrue($result['allowed']);
            $rateLimiter1->recordHit('contacts');
        }
        
        // connection-1 should be blocked
        $result1 = $rateLimiter1->isAllowed('contacts');
        $this->assertFalse($result1['allowed']);
        
        // connection-2 should still be allowed
        $result2 = $rateLimiter2->isAllowed('contacts');
        $this->assertTrue($result2['allowed']);
    }

    public function test_different_clients_have_separate_limits()
    {
        $rateLimiter1 = new LexwareRateLimiter('connection-1', 'client-1');
        $rateLimiter2 = new LexwareRateLimiter('connection-1', 'client-2');
        
        // Exhaust connection limit for client-1
        for ($i = 0; $i < 5; $i++) {
            $result = $rateLimiter1->isAllowed('contacts');
            $this->assertTrue($result['allowed']);
            $rateLimiter1->recordHit('contacts');
        }
        
        // client-1 should be blocked on connection limit
        $result1 = $rateLimiter1->isAllowed('contacts');
        $this->assertFalse($result1['allowed']);
        $this->assertEquals('connection', $result1['limitType']);
        
        // client-2 should also be blocked on connection limit (same connection)
        $result2 = $rateLimiter2->isAllowed('contacts');
        $this->assertFalse($result2['allowed']);
        $this->assertEquals('connection', $result2['limitType']);
    }

    public function test_query_parameters_are_ignored_in_endpoint_normalization()
    {
        $rateLimiter = new LexwareRateLimiter('test-connection', 'test-client');
        
        $result1 = $rateLimiter->isAllowed('contacts?page=1&size=10');
        $this->assertTrue($result1['allowed']);
        $rateLimiter->recordHit('contacts?page=1&size=10');
        
        $result2 = $rateLimiter->isAllowed('contacts?page=2&size=20');
        $this->assertTrue($result2['allowed']);
        $rateLimiter->recordHit('contacts?page=2&size=20');
        
        // Should count towards the same endpoint limit
        $result3 = $rateLimiter->isAllowed('contacts');
        $this->assertTrue($result3['allowed']);
        $rateLimiter->recordHit('contacts');
        
        // Get status to verify they're treated as the same endpoint
        $status = $rateLimiter->getStatus('contacts');
        $this->assertEquals(2, $status['connection']['tokens']); // Started with 5, used 3
    }
}