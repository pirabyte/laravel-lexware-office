<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class AutoPagingRateLimitTest extends TestCase
{
    public function test_auto_paging_iterator_respects_rate_limit_per_page_request()
    {
        // Mock RateLimiter to allow first request but block second
        RateLimiter::shouldReceive('tooManyAttempts')
            ->twice()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false, true); // First call passes, second call blocks

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1);

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(45);

        // Mock responses for pagination
        $firstPageResponse = [
            'content' => [
                ['id' => '1', 'person' => ['firstName' => 'John', 'lastName' => 'Doe']],
                ['id' => '2', 'person' => ['firstName' => 'Jane', 'lastName' => 'Smith']],
            ],
            'first' => true,
            'last' => false,
            'totalPages' => 2,
            'totalElements' => 3,
            'numberOfElements' => 2,
            'size' => 2,
            'number' => 0,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($firstPageResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Expect exception on second page due to rate limit
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(429);

        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator([], 2) as $contact) {
            $contacts[] = $contact;
        }
    }

    public function test_auto_paging_iterator_with_single_page_respects_rate_limit()
    {
        // Mock RateLimiter to pass for single request
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1);

        // Single page response (last = true)
        $singlePageResponse = [
            'content' => [
                ['id' => '1', 'person' => ['firstName' => 'John', 'lastName' => 'Doe']],
                ['id' => '2', 'person' => ['firstName' => 'Jane', 'lastName' => 'Smith']],
            ],
            'first' => true,
            'last' => true,
            'totalPages' => 1,
            'totalElements' => 2,
            'numberOfElements' => 2,
            'size' => 2,
            'number' => 0,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($singlePageResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator([], 2) as $contact) {
            $contacts[] = $contact;
        }

        $this->assertCount(2, $contacts);
        $this->assertEquals('John', $contacts[0]->getPerson()->getFirstName());
        $this->assertEquals('Jane', $contacts[1]->getPerson()->getFirstName());
    }

    public function test_auto_paging_iterator_handles_empty_pages()
    {
        // Mock RateLimiter to pass for single request
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1);

        // Empty page response
        $emptyPageResponse = [
            'content' => [],
            'first' => true,
            'last' => true,
            'totalPages' => 0,
            'totalElements' => 0,
            'numberOfElements' => 0,
            'size' => 25,
            'number' => 0,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($emptyPageResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator() as $contact) {
            $contacts[] = $contact;
        }

        $this->assertCount(0, $contacts);
    }

    public function test_auto_paging_iterator_with_custom_rate_limit_key()
    {
        // Mock RateLimiter with custom key
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'custom_key' && $maxAttempts === 10;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'custom_key' && $decay === 60;
            })
            ->andReturn(1);

        // Single page response
        $singlePageResponse = [
            'content' => [
                ['id' => '1', 'person' => ['firstName' => 'Test', 'lastName' => 'User']],
            ],
            'first' => true,
            'last' => true,
            'totalPages' => 1,
            'totalElements' => 1,
            'numberOfElements' => 1,
            'size' => 1,
            'number' => 0,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($singlePageResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set custom rate limit key and limit
        $instance->setRateLimitKey('custom_key');
        $instance->setRateLimit(10);

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator([], 1) as $contact) {
            $contacts[] = $contact;
        }

        $this->assertCount(1, $contacts);
    }

    public function test_auto_paging_iterator_successfully_processes_multiple_pages()
    {
        // Mock RateLimiter to allow all three requests (page 0, 1, 2)
        RateLimiter::shouldReceive('tooManyAttempts')
            ->times(3)
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->times(3)
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1, 2, 3);

        // Mock responses for three pages
        $page0Response = [
            'content' => [['id' => '1', 'person' => ['firstName' => 'Page', 'lastName' => 'Zero']]],
            'first' => true,
            'last' => false,
            'totalPages' => 3,
            'totalElements' => 3,
            'numberOfElements' => 1,
            'size' => 1,
            'number' => 0,
        ];

        $page1Response = [
            'content' => [['id' => '2', 'person' => ['firstName' => 'Page', 'lastName' => 'One']]],
            'first' => false,
            'last' => false,
            'totalPages' => 3,
            'totalElements' => 3,
            'numberOfElements' => 1,
            'size' => 1,
            'number' => 1,
        ];

        $page2Response = [
            'content' => [['id' => '3', 'person' => ['firstName' => 'Page', 'lastName' => 'Two']]],
            'first' => false,
            'last' => true,
            'totalPages' => 3,
            'totalElements' => 3,
            'numberOfElements' => 1,
            'size' => 1,
            'number' => 2,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page0Response)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page1Response)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($page2Response)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        $contacts = [];
        foreach ($instance->contacts()->getAutoPagingIterator([], 1) as $contact) {
            $contacts[] = $contact;
        }

        $this->assertCount(3, $contacts);
        $this->assertEquals('Zero', $contacts[0]->getPerson()->getLastName());
        $this->assertEquals('One', $contacts[1]->getPerson()->getLastName());
        $this->assertEquals('Two', $contacts[2]->getPerson()->getLastName());
    }

    public function test_paginated_resource_filter_method_respects_rate_limit()
    {
        // Mock RateLimiter to block the request
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(true);

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(30);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(429);

        // This should throw rate limit exception before making HTTP request
        $instance->contacts()->filter(['customer' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
