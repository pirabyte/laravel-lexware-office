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

        // Mock-Responses für die API-Aufrufe bei Personen-Kontakt
        $personMockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000',
                'createdDate' => '2023-06-29T15:15:09.447+02:00',
                'updatedDate' => '2023-06-29T15:15:09.447+02:00',
                'version' => 1,
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'version' => 1,
                'roles' => [
                    'customer' => ['number' => 'K-12345'],
                ],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                ],
            ])),
        ];

        $mock = new MockHandler($personMockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    public function test_it_can_create_valid_financial_transaction_json()
    {
        $fixtureFile = __DIR__.'/../Fixtures/financial-transactions/1_financial_transactions_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);

        foreach ($fixtureData as $fixtureFinancialTransaction) {
            $financialTransaction = new FinancialTransaction;

            $financialTransaction->setValueDate($fixtureFinancialTransaction['valueDate']);
            $financialTransaction->setBookingDate($fixtureFinancialTransaction['bookingDate']);
            $financialTransaction->setTransactionDate($fixtureFinancialTransaction['transactiondate']);
            $financialTransaction->setPurpose($fixtureFinancialTransaction['purpose']);
            $financialTransaction->setAmount($fixtureFinancialTransaction['amount']);
            $financialTransaction->setAdditionalInfo($fixtureFinancialTransaction['additionalInfo']);
            $financialTransaction->setRecipientOrSenderName($fixtureFinancialTransaction['recipientOrSenderName']);
            $financialTransaction->setRecipientOrSenderIban($fixtureFinancialTransaction['recipientOrSenderIban']);
            $financialTransaction->setRecipientOrSenderBic($fixtureFinancialTransaction['recipientOrSenderBic']);
            $financialTransaction->setFinancialAccountId($fixtureFinancialTransaction['financialAccountId']);
            $financialTransaction->setExternalReference($fixtureFinancialTransaction['externalReference']);

            $this->validate_financial_transaction_with_fixture_data($financialTransaction, $fixtureFinancialTransaction);
        }
    }

    public function test_it_can_parse_financial_transaction_response()
    {
        $fixtureFile = __DIR__.'/../Fixtures/financial-transactions/1_financial_transactions_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        foreach ($fixtureData as $fixtureFinancialTransaction) {
            $financialTransaction = FinancialTransaction::fromArray($fixtureFinancialTransaction);
            $this->validate_financial_transaction_with_fixture_data($financialTransaction, $fixtureFinancialTransaction);
        }
    }

    public function test_it_can_throw_exception_when_creating_more_than_25_transactions()
    {
        $transactions = [];
        for ($i = 1; $i <= 26; $i++) {
            $transactions[] = new FinancialTransaction;
        }
        $this->assertThrows(function () use ($transactions) {
            LexwareOffice::financialTransactions()->create($transactions);
        }, OutOfRangeException::class);
    }

    public function test_it_can_parse_update_response_to_correct_type()
    {
        $fixtureFile = __DIR__.'/../Fixtures/financial-transactions/2_financial_transaction_update_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);

        $response = UpdateResponse::fromArray($fixtureData);

        $this->assertEquals('016e0873-9a2b-41ca-a749-c1a3cc5945d8', $response->getId());
        $this->assertEquals('https://api.lexware-sandbox.io/v1/finance/accounts/016e0873-9a2b-41ca-a749-c1a3cc5945d8', $response->getResourceUri());
        $this->assertEquals(Carbon::parse('2023-04-05T12:30:00.000+02:00'), $response->getCreatedDate());
        $this->assertEquals(Carbon::parse('2023-04-07T13:00:00.000+02:00'), $response->getUpdatedDate());
        $this->assertEquals(2, $response->getVersion());
    }

    private function validate_financial_transaction_with_fixture_data(FinancialTransaction $transaction, array $fixtureData): void
    {
        $this->assertEquals($transaction->getValueDate(), $fixtureData['valueDate']);
        $this->assertEquals($transaction->getBookingDate(), $fixtureData['bookingDate']);
        $this->assertEquals($transaction->getTransactionDate(), $fixtureData['transactiondate']);
        $this->assertEquals($transaction->getPurpose(), $fixtureData['purpose']);
        $this->assertEquals($transaction->getAmount(), $fixtureData['amount']);
        $this->assertEquals($transaction->getAdditionalInfo(), $fixtureData['additionalInfo']);
        $this->assertEquals($transaction->getRecipientOrSenderName(), $fixtureData['recipientOrSenderName']);
        $this->assertEquals($transaction->getRecipientOrSenderIban(), $fixtureData['recipientOrSenderIban']);
        $this->assertEquals($transaction->getRecipientOrSenderBic(), $fixtureData['recipientOrSenderBic']);
        $this->assertEquals($transaction->getFinancialAccountId(), $fixtureData['financialAccountId']);
        $this->assertEquals($transaction->getExternalReference(), $fixtureData['externalReference']);
    }
}
