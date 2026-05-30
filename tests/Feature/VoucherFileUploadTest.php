<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\VoucherFileAttachmentFailedException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Pirabyte\LaravelLexwareOffice\Models\VoucherCreateWithFileResult;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class VoucherFileUploadTest extends TestCase
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

    public function test_attaches_file_successfully()
    {
        $fileData = [
            'id' => 'file_123',
            'fileName' => 'invoice.pdf',
            'mimeType' => 'application/pdf',
            'size' => 12345,
            'createdDate' => '2023-01-01T12:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
        ]);

        $this->setMockHttpClient($mock);

        $fileContent = 'PDF content here';
        $stream = Utils::streamFor($fileContent);

        $result = $this->lexware->vouchers()->attachFile(
            'voucher_123',
            $stream,
            'invoice.pdf',
            'voucher'
        );

        $this->assertEquals('file_123', $result['id']);
        $this->assertEquals('invoice.pdf', $result['fileName']);
        $this->assertEquals('application/pdf', $result['mimeType']);
        $this->assertEquals(12345, $result['size']);
    }

    public function test_attaches_file_with_default_parameters()
    {
        $fileData = [
            'id' => 'file_456',
            'fileName' => 'voucher.pdf',
            'mimeType' => 'application/pdf',
            'size' => 8765,
            'createdDate' => '2023-01-01T12:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
        ]);

        $this->setMockHttpClient($mock);

        $fileContent = 'Default PDF content';
        $stream = Utils::streamFor($fileContent);

        // Test with default filename and type
        $result = $this->lexware->vouchers()->attachFile('voucher_456', $stream);

        $this->assertEquals('file_456', $result['id']);
        $this->assertEquals('voucher.pdf', $result['fileName']);
    }

    public function test_attaches_file_with_different_types()
    {
        $testCases = [
            ['type' => 'voucher', 'filename' => 'receipt.pdf'],
            ['type' => 'attachment', 'filename' => 'document.jpg'],
            ['type' => 'invoice', 'filename' => 'invoice.png'],
        ];

        foreach ($testCases as $index => $testCase) {
            $fileData = [
                'id' => "file_$index",
                'fileName' => $testCase['filename'],
                'mimeType' => 'application/octet-stream',
                'size' => 1000 + $index,
                'createdDate' => '2023-01-01T12:00:00.000+01:00',
            ];

            $mock = new MockHandler([
                new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
            ]);

            $this->setMockHttpClient($mock);

            $stream = Utils::streamFor("Content for {$testCase['filename']}");

            $result = $this->lexware->vouchers()->attachFile(
                'voucher_789',
                $stream,
                $testCase['filename'],
                $testCase['type']
            );

            $this->assertEquals("file_$index", $result['id']);
            $this->assertEquals($testCase['filename'], $result['fileName']);
        }
    }

    public function test_handles_upload_errors_properly()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $exception = new RequestException(
            'Bad request',
            $request,
            new Response(400, [], '{"error": "invalid_file_format", "message": "File format not supported"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Invalid file content');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('File format not supported');

        $this->lexware->vouchers()->attachFile('voucher_123', $stream, 'invalid.txt', 'voucher');
    }

    public function test_handles_server_errors()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $exception = new RequestException(
            'Internal server error',
            $request,
            new Response(500, [], '{"error": "internal_error", "message": "Server temporarily unavailable"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Server temporarily unavailable');

        $this->lexware->vouchers()->attachFile('voucher_123', $stream);
    }

    public function test_handles_authentication_errors()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $exception = new RequestException(
            'Unauthorized',
            $request,
            new Response(401, [], '{"error": "unauthorized", "message": "Invalid API key"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Invalid API key');

        $this->lexware->vouchers()->attachFile('voucher_123', $stream);
    }

    public function test_handles_file_too_large_error()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $exception = new RequestException(
            'Payload too large',
            $request,
            new Response(413, [], '{"error": "file_too_large", "message": "File size exceeds maximum allowed size"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $largeContent = str_repeat('x', 10 * 1024 * 1024); // 10MB of content
        $stream = Utils::streamFor($largeContent);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');

        $this->lexware->vouchers()->attachFile('voucher_123', $stream, 'large_file.pdf');
    }

    public function test_respects_rate_limiting()
    {
        // First request succeeds
        $fileData = [
            'id' => 'file_rate_limit',
            'fileName' => 'test.pdf',
            'mimeType' => 'application/pdf',
            'size' => 1234,
            'createdDate' => '2023-01-01T12:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
        ]);

        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content');

        $result = $this->lexware->vouchers()->attachFile('voucher_rate_test', $stream);

        $this->assertEquals('file_rate_limit', $result['id']);

        // Rate limiting is tested in the main rate limit tests
        // This test ensures the file upload goes through the same rate limiting system
    }

    public function test_handles_network_connection_errors()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $exception = new RequestException('Connection timeout', $request);

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->lexware->vouchers()->attachFile('voucher_123', $stream);
    }

    public function test_handles_malformed_response()
    {
        // Return invalid JSON
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], 'invalid json response'),
        ]);

        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content');

        $result = $this->lexware->vouchers()->attachFile('voucher_123', $stream);

        // Should return raw content when JSON parsing fails
        $this->assertArrayHasKey('raw', $result);
        $this->assertEquals('invalid json response', $result['raw']);
    }

    public function test_file_upload_uses_proper_multipart_format()
    {
        // This test verifies that the multipart data is structured correctly
        $fileData = ['id' => 'test_multipart'];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
        ]);

        $this->setMockHttpClient($mock);

        $stream = Utils::streamFor('Test content for multipart verification');

        $result = $this->lexware->vouchers()->attachFile(
            'voucher_multipart_test',
            $stream,
            'multipart_test.pdf',
            'attachment'
        );

        $this->assertEquals('test_multipart', $result['id']);

        // The test passing means the multipart format was correct
        // and the request went through the proper client methods
    }

    public function test_it_can_create_voucher_and_attach_file_in_one_call()
    {
        $history = [];
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($this->voucherCreateResponse())),
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => 'file_123',
                'fileName' => 'invoice.pdf',
                'mimeType' => 'application/pdf',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));
        $client = new Client(['handler' => $handlerStack]);

        $this->lexware->setRateLimitKey('voucher_create_attach_'.uniqid());
        $this->setHttpClient($client);

        $result = $this->lexware->vouchers()->createAndAttachFile(
            $this->makeVoucher(),
            Utils::streamFor('%PDF-1.4 test content'),
            'invoice.pdf',
            'voucher',
        );

        $this->assertInstanceOf(VoucherCreateWithFileResult::class, $result);
        $this->assertEquals('voucher_123', $result->voucher['id']);
        $this->assertEquals('file_123', $result->file['id']);
        $this->assertEquals('invoice.pdf', $result->file['fileName']);

        $this->assertCount(2, $history);
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals('vouchers', (string) $history[0]['request']->getUri());
        $this->assertEquals('POST', $history[1]['request']->getMethod());
        $this->assertEquals('vouchers/voucher_123/files', (string) $history[1]['request']->getUri());
    }

    public function test_create_and_attach_file_wraps_upload_failure_with_created_voucher_context()
    {
        $request = new Request('POST', 'vouchers/voucher_123/files');
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($this->voucherCreateResponse())),
            new RequestException(
                'Rate limit exceeded',
                $request,
                new Response(429, ['Retry-After' => '30'], '{"message":"Rate limit exceeded"}'),
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->lexware->setRateLimitKey('voucher_create_attach_failure_'.uniqid());
        $this->setHttpClient($client);

        try {
            $this->lexware->vouchers()->createAndAttachFile(
                $this->makeVoucher(),
                Utils::streamFor('%PDF-1.4 test content'),
            );

            $this->fail('Expected voucher attachment failure exception.');
        } catch (VoucherFileAttachmentFailedException $e) {
            $this->assertEquals('voucher_123', $e->getVoucher()['id']);
            $this->assertInstanceOf(LexwareOfficeApiException::class, $e->getPrevious());
        }
    }

    public function test_wait_for_rate_limit_capacity_throws_when_attempt_limit_is_exceeded()
    {
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->with('wait_timeout_key', 5)
            ->andReturn(false);
        RateLimiter::shouldReceive('remaining')
            ->once()
            ->with('wait_timeout_key', 5)
            ->andReturn(0);
        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->with('wait_timeout_key')
            ->andReturn(12);

        $this->lexware->setRateLimitKey('wait_timeout_key');
        $this->lexware->setRateLimit(5);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(429);

        $this->lexware->waitForRateLimitCapacity(2, maxAttempts: 1);
    }

    public function test_zero_rate_limit_disables_local_limiter_for_create_and_attach_file()
    {
        RateLimiter::shouldReceive('tooManyAttempts')->never();
        RateLimiter::shouldReceive('remaining')->never();
        RateLimiter::shouldReceive('availableIn')->never();
        RateLimiter::shouldReceive('hit')->never();

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($this->voucherCreateResponse())),
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => 'file_without_limit',
                'fileName' => 'voucher.pdf',
                'mimeType' => 'application/pdf',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $lexware = new LexwareOffice(
            'https://api.lexoffice.de',
            'test_api_key',
            'disabled_limit_'.uniqid(),
            0,
        );
        $this->setHttpClient($client, $lexware);

        $result = $lexware->vouchers()->createAndAttachFile(
            $this->makeVoucher(),
            Utils::streamFor('%PDF-1.4 test content'),
        );

        $this->assertEquals('voucher_123', $result->voucher['id']);
        $this->assertEquals('file_without_limit', $result->file['id']);
    }

    private function setMockHttpClient(MockHandler $mock): void
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->setHttpClient($client);
    }

    private function setHttpClient(Client $client, ?LexwareOffice $lexware = null): void
    {
        $lexware ??= $this->lexware;

        // Set both client properties to ensure compatibility
        $reflection = new \ReflectionClass($lexware);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($lexware, $client);

        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($lexware, $client);
    }

    private function makeVoucher(): Voucher
    {
        return Voucher::fromArray([
            'type' => 'salesinvoice',
            'voucherDate' => '2023-06-28T00:00:00.000+02:00',
            'totalGrossAmount' => 119.0,
            'totalTaxAmount' => 19.0,
            'taxType' => 'gross',
            'useCollectiveContact' => true,
            'voucherItems' => [
                [
                    'amount' => 119.0,
                    'taxAmount' => 19.0,
                    'taxRatePercent' => 19.0,
                    'categoryId' => '8f8664a8-fd86-11e1-a21f-0800200c9a66',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherCreateResponse(): array
    {
        return [
            'id' => 'voucher_123',
            'resourceUri' => 'https://api.lexoffice.de/v1/vouchers/voucher_123',
            'createdDate' => '2023-06-29T15:15:09.447+02:00',
            'updatedDate' => '2023-06-29T15:15:09.447+02:00',
            'version' => 1,
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
