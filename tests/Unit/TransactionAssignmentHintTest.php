<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Pirabyte\LaravelLexwareOffice\Dto\TransactionAssignmentHints\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Mappers\TransactionAssignmentHints\TransactionAssignmentHintWriteMapper;
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
            'externalReference' => 'C205CD6E49F319AE9B03CAD01F555E2B9F188407',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice Client
        $instance = $this->app->make('lexware-office');

        $instance->setClient($client);

        // Create Hint
        $hint = new TransactionAssignmentHint(
            voucherId: 'ee143016-f177-4da7-a3b7-513a525a25a4',
            externalReference: 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'
        );

        // Test Create
        $result = $instance->transactionAssignmentHints()->create($hint);

        // Assertions
        $this->assertInstanceOf(TransactionAssignmentHint::class, $result);
        $this->assertEquals('ee143016-f177-4da7-a3b7-513a525a25a4', $result->voucherId);
        $this->assertEquals('C205CD6E49F319AE9B03CAD01F555E2B9F188407', $result->externalReference);
    }

    /** @test */
    public function it_serializes_to_json_correctly()
    {
        $hint = new TransactionAssignmentHint(
            voucherId: 'ee143016-f177-4da7-a3b7-513a525a25a4',
            externalReference: 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'
        );

        $jsonBody = TransactionAssignmentHintWriteMapper::toJsonBody($hint);
        $payload = json_decode($jsonBody->json, true);

        $this->assertEquals($hint->voucherId, $payload['voucherId']);
        $this->assertEquals($hint->externalReference, $payload['externalReference']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
