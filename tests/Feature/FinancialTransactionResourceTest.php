<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OutOfRangeException;
use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialTransactionCreateBatch;
use Pirabyte\LaravelLexwareOffice\Dto\Common\UpdateResult;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionCreate;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionUpdate;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialTransactionCreateBatchMapper;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FinancialTransactionResourceTest extends TestCase
{
    public function test_it_can_create_financial_transactions(): void
    {
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_response.json'), true);

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $transactions = [];
        foreach ($requestFixture as $transactionData) {
            $transactions[] = new FinancialTransactionCreate(
                valueDate: new DateTimeImmutable($transactionData['valueDate']),
                bookingDate: new DateTimeImmutable($transactionData['bookingDate']),
                transactionDate: new DateTimeImmutable($transactionData['transactiondate']),
                purpose: $transactionData['purpose'],
                amount: (float) $transactionData['amount'],
                financialAccountId: $transactionData['financialAccountId'],
                externalReference: $transactionData['externalReference'] ?? null,
                additionalInfo: $transactionData['additionalInfo'] ?? null,
                recipientOrSenderName: $transactionData['recipientOrSenderName'] ?? null,
                recipientOrSenderEmail: $transactionData['recipientOrSenderEmail'] ?? null,
                recipientOrSenderIban: $transactionData['recipientOrSenderIban'] ?? null,
                recipientOrSenderBic: $transactionData['recipientOrSenderBic'] ?? null,
            );
        }

        $batch = FinancialTransactionCreateBatch::fromTransactions(...$transactions);

        $result = LexwareOffice::financialTransactions()->create($batch);

        $this->assertCount(2, $result);

        $first = $result->get(0);
        $this->assertNotNull($first);
        $this->assertEquals('80a0d09d-d8ff-4ab1-b195-f3d2548cf4fc', $first->financialTransactionId);
        $this->assertEquals('verwendungszweck1', $first->purpose);
        $this->assertEquals(120.01, $first->amount);

        $second = $result->get(1);
        $this->assertNotNull($second);
        $this->assertEquals('6bdf5b9f-d30b-48ca-9a7d-905444f3aca8', $second->financialTransactionId);
        $this->assertEquals('verwendungszweck2', $second->purpose);
        $this->assertEquals(120.02, $second->amount);
    }

    public function test_it_can_create_financial_transactions_with_fee(): void
    {
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_with_fee_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_with_fee_response.json'), true);

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $transactions = [];
        foreach ($requestFixture as $transactionData) {
            $transactions[] = new FinancialTransactionCreate(
                valueDate: new DateTimeImmutable($transactionData['valueDate']),
                bookingDate: new DateTimeImmutable($transactionData['bookingDate']),
                transactionDate: new DateTimeImmutable($transactionData['transactionDate']),
                purpose: $transactionData['purpose'],
                amount: (float) $transactionData['amount'],
                financialAccountId: $transactionData['financialAccountId'],
                externalReference: $transactionData['externalReference'] ?? null,
                additionalInfo: $transactionData['additionalInfo'] ?? null,
                recipientOrSenderName: $transactionData['recipientOrSenderName'] ?? null,
                recipientOrSenderEmail: $transactionData['recipientOrSenderEmail'] ?? null,
                recipientOrSenderIban: $transactionData['recipientOrSenderIban'] ?? null,
                recipientOrSenderBic: $transactionData['recipientOrSenderBic'] ?? null,
                feeAmount: isset($transactionData['feeAmount']) ? (float) $transactionData['feeAmount'] : null,
                feeTaxRatePercentage: isset($transactionData['feeTaxRatePercentage']) ? (float) $transactionData['feeTaxRatePercentage'] : null,
                feePostingCategoryId: $transactionData['feePostingCategoryId'] ?? null,
            );
        }

        $batch = FinancialTransactionCreateBatch::fromTransactions(...$transactions);

        $result = LexwareOffice::financialTransactions()->create($batch);

        $this->assertCount(2, $result);
        $first = $result->get(0);
        $this->assertNotNull($first);
        $this->assertEquals('f5cbd925-3f5f-44ab-802b-896536e843b4', $first->financialTransactionId);

        $second = $result->get(1);
        $this->assertNotNull($second);
        $this->assertEquals('61543e90-df7c-40ac-8c3d-5e57c6d506e8', $second->financialTransactionId);
    }

    public function test_it_can_update_financial_transaction(): void
    {
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/update_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/update_response.json'), true);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $update = new FinancialTransactionUpdate(
            lockVersion: (int) $requestFixture['lockVersion'],
            valueDate: new DateTimeImmutable($requestFixture['valueDate']),
            bookingDate: null,
            transactionDate: new DateTimeImmutable($requestFixture['transactionDate']),
            purpose: $requestFixture['purpose'],
            amount: (float) $requestFixture['amount'],
            financialAccountId: $requestFixture['financialAccountId'],
            externalReference: $requestFixture['externalReference'] ?? null,
            additionalInfo: $requestFixture['additionalInfo'] ?? null,
            recipientOrSenderName: $requestFixture['recipientOrSenderName'] ?? null,
            recipientOrSenderEmail: $requestFixture['recipientOrSenderEmail'] ?? null,
            recipientOrSenderIban: $requestFixture['recipientOrSenderIban'] ?? null,
            recipientOrSenderBic: $requestFixture['recipientOrSenderBic'] ?? null,
        );

        $result = LexwareOffice::financialTransactions()->update(
            '016e0873-9a2b-41ca-a749-c1a3cc5945d8',
            $update
        );

        $this->assertInstanceOf(UpdateResult::class, $result);
        $this->assertEquals('016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->id);
        $this->assertEquals(2, $result->version);
        $this->assertEquals('https://api.lexware-sandbox.io/v1/finance/accounts/016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->resourceUri);

        $this->assertEquals((new DateTimeImmutable('2023-04-05T12:30:00.000+02:00'))->getTimestamp(), $result->createdDate->getTimestamp());
        $this->assertEquals((new DateTimeImmutable('2023-04-07T13:00:00.000+02:00'))->getTimestamp(), $result->updatedDate->getTimestamp());
    }

    public function test_it_can_get_latest_financial_transaction(): void
    {
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/latest_response.json'), true);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $result = LexwareOffice::financialTransactions()->latest('ebb47780-f417-4652-8d7d-727fd00e3a5f');

        $this->assertInstanceOf(FinancialTransaction::class, $result);
        $this->assertEquals('016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->financialTransactionId);
        $this->assertEquals('2023-06-30T08:57:59 Karte2 2026-12', $result->purpose);
        $this->assertEquals(-22.33, $result->amount);
        $this->assertEquals('ebb47780-f417-4652-8d7d-727fd00e3a5f', $result->financialAccountId);
        $this->assertEquals(1, $result->lockVersion);
        $this->assertNull($result->bookingDate);
    }

    public function test_it_returns_null_when_no_latest_transaction_found(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $result = LexwareOffice::financialTransactions()->latest('non-existing-account-id');

        $this->assertNull($result);
    }

    public function test_it_can_throw_exception_when_creating_more_than_25_transactions(): void
    {
        $transactions = [];
        for ($i = 1; $i <= 26; $i++) {
            $transactions[] = new FinancialTransactionCreate(
                valueDate: new DateTimeImmutable('2023-05-28T00:00:00+02:00'),
                bookingDate: new DateTimeImmutable('2023-05-20T00:00:00+02:00'),
                transactionDate: new DateTimeImmutable('2023-05-28T00:00:00+02:00'),
                purpose: 'Transaction '.$i,
                amount: 1.0,
                financialAccountId: 'account-123',
            );
        }

        $this->assertThrows(function () use ($transactions) {
            FinancialTransactionCreateBatch::fromTransactions(...$transactions);
        }, OutOfRangeException::class);
    }

    public function test_it_serializes_transaction_create_request_correctly(): void
    {
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_request.json'), true);

        $transactions = [];
        foreach ($requestFixture as $transactionData) {
            $transactions[] = new FinancialTransactionCreate(
                valueDate: new DateTimeImmutable($transactionData['valueDate']),
                bookingDate: new DateTimeImmutable($transactionData['bookingDate']),
                transactionDate: new DateTimeImmutable($transactionData['transactiondate']),
                purpose: $transactionData['purpose'],
                amount: (float) $transactionData['amount'],
                financialAccountId: $transactionData['financialAccountId'],
                externalReference: $transactionData['externalReference'] ?? null,
                additionalInfo: $transactionData['additionalInfo'] ?? null,
                recipientOrSenderName: $transactionData['recipientOrSenderName'] ?? null,
                recipientOrSenderEmail: $transactionData['recipientOrSenderEmail'] ?? null,
                recipientOrSenderIban: $transactionData['recipientOrSenderIban'] ?? null,
                recipientOrSenderBic: $transactionData['recipientOrSenderBic'] ?? null,
            );
        }

        $batch = FinancialTransactionCreateBatch::fromTransactions(...$transactions);
        $body = FinancialTransactionCreateBatchMapper::toJsonBody($batch);

        $this->assertEquals($requestFixture, json_decode($body->json, true));
    }
}


