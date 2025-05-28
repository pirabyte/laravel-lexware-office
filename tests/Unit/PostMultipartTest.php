<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class PostMultipartTest extends TestCase
{
    protected LexwareOffice $lexware;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->lexware = new LexwareOffice(
            'https://api.lexoffice.de',
            'test_api_key'
        );
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('lexware_office_api');
        parent::tearDown();
    }

    public function test_postMultipart_sends_multipart_request_successfully()
    {
        $responseData = [
            'id' => 'upload_123',
            'status' => 'uploaded',
            'size' => 1024
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseData)),
        ]);

        $this->setMockHttpClient($mock);

        $multipartData = [
            [
                'name' => 'file',
                'contents' => 'file content here',
                'filename' => 'test.pdf'
            ],
            [
                'name' => 'type',
                'contents' => 'document'
            ]
        ];

        $result = $this->lexware->postMultipart('upload/files', $multipartData);

        $this->assertEquals('upload_123', $result['id']);
        $this->assertEquals('uploaded', $result['status']);
        $this->assertEquals(1024, $result['size']);
    }

    public function test_postMultipart_handles_http_errors()
    {
        $request = new Request('POST', 'upload/files');
        $exception = new RequestException(
            'Bad request',
            $request,
            new Response(400, [], '{"error": "invalid_multipart_data"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $multipartData = [
            ['name' => 'invalid', 'contents' => 'data']
        ];

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('invalid_multipart_data');

        $this->lexware->postMultipart('upload/files', $multipartData);
    }

    public function test_postMultipart_respects_rate_limiting()
    {
        // Set a very low rate limit for testing
        $this->lexware->setRateLimit(1);

        $responseData = ['id' => 'rate_test'];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData)),
        ]);

        $this->setMockHttpClient($mock);

        $multipartData = [['name' => 'test', 'contents' => 'data']];

        // First request should succeed
        $result = $this->lexware->postMultipart('test/endpoint', $multipartData);
        $this->assertEquals('rate_test', $result['id']);

        // Second request should trigger rate limit
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->lexware->postMultipart('test/endpoint', $multipartData);
    }

    public function test_postMultipart_handles_malformed_json_response()
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], 'invalid json'),
        ]);

        $this->setMockHttpClient($mock);

        $multipartData = [['name' => 'test', 'contents' => 'data']];

        $result = $this->lexware->postMultipart('test/endpoint', $multipartData);

        // Should return raw content when JSON parsing fails
        $this->assertArrayHasKey('raw', $result);
        $this->assertEquals('invalid json', $result['raw']);
    }

    public function test_postMultipart_handles_network_errors()
    {
        $request = new Request('POST', 'test/endpoint');
        $exception = new RequestException('Network error: Connection timeout', $request);

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $multipartData = [['name' => 'test', 'contents' => 'data']];

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Network error: Connection timeout');

        $this->lexware->postMultipart('test/endpoint', $multipartData);
    }

    public function test_postMultipart_handles_server_errors()
    {
        $request = new Request('POST', 'test/endpoint');
        $exception = new RequestException(
            'Server error',
            $request,
            new Response(500, [], '{"error": "internal_server_error", "message": "Service temporarily unavailable"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $multipartData = [['name' => 'test', 'contents' => 'data']];

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Service temporarily unavailable');

        $this->lexware->postMultipart('test/endpoint', $multipartData);
    }

    public function test_postMultipart_handles_authentication_errors()
    {
        $request = new Request('POST', 'test/endpoint');
        $exception = new RequestException(
            'Unauthorized',
            $request,
            new Response(401, [], '{"error": "unauthorized", "message": "Invalid or expired token"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $multipartData = [['name' => 'test', 'contents' => 'data']];

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Invalid or expired token');

        $this->lexware->postMultipart('test/endpoint', $multipartData);
    }

    public function test_postMultipart_with_empty_multipart_data()
    {
        $responseData = ['message' => 'empty_upload_processed'];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData)),
        ]);

        $this->setMockHttpClient($mock);

        $result = $this->lexware->postMultipart('test/endpoint', []);

        $this->assertEquals('empty_upload_processed', $result['message']);
    }

    public function test_postMultipart_with_complex_multipart_data()
    {
        $responseData = [
            'files_processed' => 2,
            'metadata_received' => true
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData)),
        ]);

        $this->setMockHttpClient($mock);

        $multipartData = [
            [
                'name' => 'file1',
                'contents' => 'First file content',
                'filename' => 'document1.pdf',
                'headers' => ['Content-Type' => 'application/pdf']
            ],
            [
                'name' => 'file2', 
                'contents' => 'Second file content',
                'filename' => 'document2.jpg'
            ],
            [
                'name' => 'metadata',
                'contents' => '{"category": "invoices", "priority": "high"}'
            ],
            [
                'name' => 'tags',
                'contents' => 'important,urgent'
            ]
        ];

        $result = $this->lexware->postMultipart('complex/upload', $multipartData);

        $this->assertEquals(2, $result['files_processed']);
        $this->assertTrue($result['metadata_received']);
    }

    private function setMockHttpClient(MockHandler $mock): void
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->lexware);
        
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($this->lexware, $client);
        
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->lexware, $client);
    }
}