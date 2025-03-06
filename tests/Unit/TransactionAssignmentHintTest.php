<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class TransactionAssignmentHintTest extends TestCase
{
    /** @test */
    public function it_can_create_transaction_assignment_hint()
    {
        // Mock RateLimiter
        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
        RateLimiter::shouldReceive('hit')->andReturn(1);

        // Mock Response
        $mockResponse = [
            'voucherId' => 'ee143016-f177-4da7-a3b7-513a525a25a4',
            'externalReference' => 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice Client
        $instance = $this->app->make('lexware-office');

        // Set Client
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Create Hint
        $hint = TransactionAssignmentHint::fromArray([
            'voucherId' => 'ee143016-f177-4da7-a3b7-513a525a25a4',
            'externalReference' => 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'
        ]);

        // Test Create
        $result = $instance->transactionAssignmentHints()->create($hint);

        // Assertions
        $this->assertInstanceOf(TransactionAssignmentHint::class, $result);
        $this->assertEquals('ee143016-f177-4da7-a3b7-513a525a25a4', $result->getVoucherId());
        $this->assertEquals('C205CD6E49F319AE9B03CAD01F555E2B9F188407', $result->getExternalReference());
    }

    /** @test */
    public function it_serializes_to_json_correctly()
    {
        $data = [
            'voucherId' => 'ee143016-f177-4da7-a3b7-513a525a25a4',
            'externalReference' => 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'
        ];

        $hint = TransactionAssignmentHint::fromArray($data);
        $serialized = $hint->jsonSerialize();

        $this->assertEquals($data['voucherId'], $serialized['voucherId']);
        $this->assertEquals($data['externalReference'], $serialized['externalReference']);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}