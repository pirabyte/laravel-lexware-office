<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use OutOfRangeException;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Responses\UpdateResponse;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FinancialTransactionResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_can_create_financial_transactions()
    {
        // Load fixtures
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_response.json'), true);

        // Setup mock
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Create transactions from request fixture
        $transactions = [];
        foreach ($requestFixture as $transactionData) {
            $transaction = new FinancialTransaction();
            $transaction->setValueDate($transactionData['valueDate']);
            $transaction->setBookingDate($transactionData['bookingDate']);
            $transaction->setTransactionDate($transactionData['transactiondate']);
            $transaction->setPurpose($transactionData['purpose']);
            $transaction->setAmount($transactionData['amount']);
            $transaction->setAdditionalInfo($transactionData['additionalInfo']);
            $transaction->setRecipientOrSenderName($transactionData['recipientOrSenderName']);
            $transaction->setRecipientOrSenderIban($transactionData['recipientOrSenderIban']);
            $transaction->setRecipientOrSenderBic($transactionData['recipientOrSenderBic']);
            $transaction->setFinancialAccountId($transactionData['financialAccountId']);
            $transaction->setExternalReference($transactionData['externalReference']);
            $transactions[] = $transaction;
        }

        // Execute create
        $result = LexwareOffice::financialTransactions()->create($transactions);

        // Verify result
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FinancialTransaction::class, $result[0]);
        $this->assertEquals('80a0d09d-d8ff-4ab1-b195-f3d2548cf4fc', $result[0]->getFinancialTransactionId());
        $this->assertEquals('verwendungszweck1', $result[0]->getPurpose());
        $this->assertEquals(120.01, $result[0]->getAmount());
        
        $this->assertInstanceOf(FinancialTransaction::class, $result[1]);
        $this->assertEquals('6bdf5b9f-d30b-48ca-9a7d-905444f3aca8', $result[1]->getFinancialTransactionId());
        $this->assertEquals('verwendungszweck2', $result[1]->getPurpose());
        $this->assertEquals(120.02, $result[1]->getAmount());
    }

    public function test_it_can_create_financial_transactions_with_fee()
    {
        // Load fixtures
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_with_fee_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/create_with_fee_response.json'), true);

        // Setup mock
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Create transactions with fee from fixture
        $transactions = [];
        foreach ($requestFixture as $transactionData) {
            $transaction = new FinancialTransaction();
            $transaction->setValueDate($transactionData['valueDate']);
            $transaction->setBookingDate($transactionData['bookingDate']);
            $transaction->setTransactionDate($transactionData['transactionDate']);
            $transaction->setPurpose($transactionData['purpose']);
            $transaction->setAmount($transactionData['amount']);
            $transaction->setFeeAmount($transactionData['feeAmount']);
            $transaction->setFeeTaxRatePercentage($transactionData['feeTaxRatePercentage']);
            $transaction->setFeePostingCategoryId($transactionData['feePostingCategoryId']);
            if (isset($transactionData['additionalInfo'])) {
                $transaction->setAdditionalInfo($transactionData['additionalInfo']);
            }
            $transaction->setFinancialAccountId($transactionData['financialAccountId']);
            $transaction->setRecipientOrSenderName($transactionData['recipientOrSenderName']);
            $transaction->setExternalReference($transactionData['externalReference']);
            $transactions[] = $transaction;
        }

        // Execute create
        $result = LexwareOffice::financialTransactions()->create($transactions);

        // Verify result
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FinancialTransaction::class, $result[0]);
        $this->assertEquals('f5cbd925-3f5f-44ab-802b-896536e843b4', $result[0]->getFinancialTransactionId());
        $this->assertEquals(-5.00, $result[0]->getFeeAmount());
        $this->assertEquals(0, $result[0]->getFeeTaxRatePercentage());
        
        $this->assertInstanceOf(FinancialTransaction::class, $result[1]);
        $this->assertEquals('61543e90-df7c-40ac-8c3d-5e57c6d506e8', $result[1]->getFinancialTransactionId());
        $this->assertEquals(-10.00, $result[1]->getFeeAmount());
        $this->assertEquals(19, $result[1]->getFeeTaxRatePercentage());
    }

    public function test_it_can_update_financial_transaction()
    {
        // Load fixtures
        $requestFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/update_request.json'), true);
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/update_response.json'), true);

        // Setup mock
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Create transaction from fixture
        $transaction = new FinancialTransaction();
        $transaction->setFinancialTransactionId($requestFixture['financialTransactionId']);
        $transaction->setValueDate($requestFixture['valueDate']);
        $transaction->setBookingDate($requestFixture['bookingDate']);
        $transaction->setPurpose($requestFixture['purpose']);
        $transaction->setAmount($requestFixture['amount']);
        $transaction->setFinancialAccountId($requestFixture['financialAccountId']);
        $transaction->setExternalReference($requestFixture['externalReference']);
        $transaction->setLockVersion($requestFixture['lockVersion']);

        // Execute update
        $result = LexwareOffice::financialTransactions()->update(
            '016e0873-9a2b-41ca-a749-c1a3cc5945d8',
            $transaction
        );

        // Verify result
        $this->assertInstanceOf(UpdateResponse::class, $result);
        $this->assertEquals('016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->getId());
        $this->assertEquals(2, $result->getVersion());
        $this->assertEquals('https://api.lexware-sandbox.io/v1/finance/accounts/016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->getResourceUri());
        $this->assertEquals(Carbon::parse('2023-04-05T12:30:00.000+02:00'), $result->getCreatedDate());
        $this->assertEquals(Carbon::parse('2023-04-07T13:00:00.000+02:00'), $result->getUpdatedDate());
    }

    public function test_it_can_get_latest_financial_transaction()
    {
        // Load fixture
        $responseFixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/financial-transactions/latest_response.json'), true);

        // Setup mock
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseFixture)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Execute latest
        $result = LexwareOffice::financialTransactions()->latest('ebb47780-f417-4652-8d7d-727fd00e3a5f');

        // Verify result
        $this->assertInstanceOf(FinancialTransaction::class, $result);
        $this->assertEquals('016e0873-9a2b-41ca-a749-c1a3cc5945d8', $result->getFinancialTransactionId());
        $this->assertEquals('2023-06-30T08:57:59 Karte2 2026-12', $result->getPurpose());
        $this->assertEquals(-22.33, $result->getAmount());
        $this->assertEquals('ebb47780-f417-4652-8d7d-727fd00e3a5f', $result->getFinancialAccountId());
        $this->assertEquals(1, $result->getLockVersion());
    }

    public function test_it_returns_null_when_no_latest_transaction_found()
    {
        // Setup mock with empty response
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $instance = app('lexware-office');
        $instance->setClient($client);

        // Execute latest
        $result = LexwareOffice::financialTransactions()->latest('non-existing-account-id');

        // Verify result
        $this->assertNull($result);
    }

    public function test_it_can_throw_exception_when_creating_more_than_25_transactions()
    {
        $transactions = [];
        for ($i = 1; $i <= 26; $i++) {
            $transaction = new FinancialTransaction();
            $transaction->setPurpose('Transaction ' . $i);
            $transactions[] = $transaction;
        }
        
        $this->assertThrows(function () use ($transactions) {
            LexwareOffice::financialTransactions()->create($transactions);
        }, OutOfRangeException::class);
    }

    public function test_it_requires_lock_version_for_updates()
    {
        $transaction = new FinancialTransaction();
        $transaction->setPurpose('Updated purpose');
        $transaction->setAmount(100.00);
        // No lockVersion set

        $this->assertThrows(function () use ($transaction) {
            LexwareOffice::financialTransactions()->update('some-id', $transaction);
        }, \InvalidArgumentException::class, 'lockVersion is required for updates (optimistic locking)');
    }

    public function test_it_can_parse_financial_transaction_from_array()
    {
        $data = [
            'financialTransactionId' => '123',
            'valueDate' => '2023-05-28T00:00:00+02:00',
            'bookingDate' => '2023-05-20T00:00:00+02:00',
            'transactionDate' => '2023-05-28T00:00:00+02:00',
            'purpose' => 'Test purpose',
            'amount' => 100.50,
            'financialAccountId' => 'account-123',
            'lockVersion' => 1,
            'feeAmount' => -5.00,
            'feeTaxRatePercentage' => 19,
            'bookingText' => 'Booking text',
            'virtualAccountId' => 'virtual-123',
            'ignore' => false,
            'ignoreReason' => null,
        ];

        $transaction = FinancialTransaction::fromArray($data);

        $this->assertEquals('123', $transaction->getFinancialTransactionId());
        $this->assertEquals('2023-05-28T00:00:00+02:00', $transaction->getValueDate());
        $this->assertEquals('2023-05-20T00:00:00+02:00', $transaction->getBookingDate());
        $this->assertEquals('2023-05-28T00:00:00+02:00', $transaction->getTransactionDate());
        $this->assertEquals('Test purpose', $transaction->getPurpose());
        $this->assertEquals(100.50, $transaction->getAmount());
        $this->assertEquals('account-123', $transaction->getFinancialAccountId());
        $this->assertEquals(1, $transaction->getLockVersion());
        $this->assertEquals(-5.00, $transaction->getFeeAmount());
        $this->assertEquals(19, $transaction->getFeeTaxRatePercentage());
        $this->assertEquals('Booking text', $transaction->getBookingText());
        $this->assertEquals('virtual-123', $transaction->getVirtualAccountId());
        $this->assertFalse($transaction->getIgnore());
        $this->assertNull($transaction->getIgnoreReason());
    }

    public function test_it_serializes_transaction_correctly_for_create()
    {
        $transaction = new FinancialTransaction();
        $transaction->setValueDate('2023-05-28T00:00:00+02:00');
        $transaction->setBookingDate('2023-05-20T00:00:00+02:00');
        $transaction->setTransactionDate('2023-05-28T00:00:00+02:00');
        $transaction->setPurpose('Test purpose');
        $transaction->setAmount(100.50);
        $transaction->setFinancialAccountId('account-123');
        $transaction->setExternalReference('ext-ref-123');

        $serialized = $transaction->jsonSerialize();

        // For create (no financialTransactionId), transactionDate should be 'transactiondate'
        $this->assertArrayHasKey('transactiondate', $serialized);
        $this->assertEquals('2023-05-28T00:00:00+02:00', $serialized['transactiondate']);
        $this->assertEquals('2023-05-28T00:00:00+02:00', $serialized['valueDate']);
        $this->assertEquals('2023-05-20T00:00:00+02:00', $serialized['bookingDate']);
        $this->assertEquals('Test purpose', $serialized['purpose']);
        $this->assertEquals(100.50, $serialized['amount']);
        $this->assertEquals('account-123', $serialized['financialAccountId']);
        $this->assertEquals('ext-ref-123', $serialized['externalReference']);
    }
}
