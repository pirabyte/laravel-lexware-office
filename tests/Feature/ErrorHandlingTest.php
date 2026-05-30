<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test handling of 401 Unauthorized errors
     */
    public function test_it_handles_unauthorized_errors()
    {
        // Setup mock response
        $mockResponses = [
            new Response(401, [
                'Content-Type' => 'application/json',
                'x-amzn-ErrorType' => 'UnauthorizedException',
            ], json_encode([
                'message' => 'Unauthorized',
                'details' => 'API key is invalid or expired',
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test exception handling
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('test-id');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Unauthorized', $exception->getMessage());
                $this->assertEquals(401, $exception->getStatusCode());
                $this->assertEquals(LexwareOfficeApiException::ERROR_TYPE_AUTHORIZATION, $exception->getErrorType());
                $this->assertEquals(json_encode([
                    'message' => 'Unauthorized',
                    'details' => 'API key is invalid or expired',
                ]), $exception->getError()->rawBody);
                $this->assertTrue($exception->isAuthError());

                return true;
            }
        );
    }

    /**
     * Test handling of 429 Rate Limit errors
     */
    public function test_it_handles_rate_limit_errors()
    {
        // Setup mock response
        $mockResponses = [
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '30',
                'x-amzn-ErrorType' => 'ThrottlingException',
            ], json_encode([
                'message' => 'Rate limit exceeded',
                'details' => 'You have exceeded the API rate limit',
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test exception handling
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('test-id');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Rate limit exceeded', $exception->getMessage());
                $this->assertEquals(429, $exception->getStatusCode());
                $this->assertEquals(LexwareOfficeApiException::ERROR_TYPE_RATE_LIMIT, $exception->getErrorType());

                // Check for retry information
                $this->assertTrue($exception->isRateLimitError());
                $this->assertEquals(30, $exception->getRetryAfter());
                $this->assertStringContainsString('30 seconds', $exception->getRetrySuggestion());

                return true;
            }
        );
    }

    /**
     * Test handling of 400 Validation errors
     */
    public function test_it_handles_validation_errors()
    {
        // Setup mock response
        $mockResponses = [
            new Response(400, [
                'Content-Type' => 'application/json',
                'x-amzn-ErrorType' => 'ValidationException',
            ], json_encode([
                'message' => 'Validation error',
                'details' => 'The request contains invalid data',
                'validationErrors' => [
                    ['field' => 'email', 'message' => 'Invalid email format'],
                    ['field' => 'name', 'message' => 'Name is required'],
                ],
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test exception handling
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('test-id');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Validation error', $exception->getMessage());
                $this->assertEquals(400, $exception->getStatusCode());
                $this->assertEquals(LexwareOfficeApiException::ERROR_TYPE_VALIDATION, $exception->getErrorType());
                $this->assertTrue($exception->isValidationError());

                // Check validation error details
                $responseData = json_decode($exception->getError()->rawBody, true);
                $this->assertIsArray($responseData);
                $this->assertArrayHasKey('validationErrors', $responseData);
                $this->assertCount(2, $responseData['validationErrors']);

                return true;
            }
        );
    }

    /**
     * Test handling of 404 Not Found errors
     */
    public function test_it_handles_not_found_errors()
    {
        // Setup mock response
        $mockResponses = [
            new Response(404, [
                'Content-Type' => 'application/json',
                'x-amzn-ErrorType' => 'ResourceNotFoundException',
            ], json_encode([
                'message' => 'Resource not found',
                'details' => 'The requested resource could not be found',
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test exception handling
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('invalid-id');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Resource not found', $exception->getMessage());
                $this->assertEquals(404, $exception->getStatusCode());
                $this->assertEquals(LexwareOfficeApiException::ERROR_TYPE_RESOURCE_NOT_FOUND, $exception->getErrorType());
                $this->assertTrue($exception->isNotFoundError());

                return true;
            }
        );
    }

    /**
     * Test handling of 500 Server errors
     */
    public function test_it_handles_server_errors()
    {
        // Setup mock response
        $mockResponses = [
            new Response(500, [
                'Content-Type' => 'application/json',
                'x-amzn-ErrorType' => 'InternalServerException',
            ], json_encode([
                'message' => 'Internal server error',
                'details' => 'An unexpected error occurred',
            ])),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Replace client with mock handler
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test exception handling
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('test-id');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Internal server error', $exception->getMessage());
                $this->assertEquals(500, $exception->getStatusCode());
                $this->assertEquals(LexwareOfficeApiException::ERROR_TYPE_SERVER_ERROR, $exception->getErrorType());
                $this->assertTrue($exception->isServerError());

                return true;
            }
        );
    }
}
