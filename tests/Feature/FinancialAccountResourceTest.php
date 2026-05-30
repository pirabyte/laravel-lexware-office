<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountCreate;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountQuery;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialAccountMapper;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FinancialAccountResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/finance-accounts/1_create_financial_account_response.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/finance-accounts/2_get_financial_account_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    /** @test */
    public function it_can_create_and_retrieve_a_financial_account(): void
    {
        $financialAccount = new FinancialAccountCreate(
            financialAccountId: 'a32ff243-f681-4a4f-accd-832f48a7aebe',
            type: FinancialAccountType::PAYMENT_PROVIDER,
            accountSystem: AccountSystem::PAYMENT_PROVIDER,
            name: 'Stripe (Demo Company 123)'
        );

        $savedFinancialAccount = LexwareOffice::financialAccounts()->create($financialAccount);

        $this->assertEquals('a32ff243-f681-4a4f-accd-832f48a7aebe', $savedFinancialAccount->financialAccountId);
        $this->assertEquals('Stripe (Demo Company 12345)', $savedFinancialAccount->name);
        $this->assertEquals(FinancialAccountType::PAYMENT_PROVIDER, $savedFinancialAccount->type);
        $this->assertEquals('acct_1PiC0uRqvWc6M8Pc', $savedFinancialAccount->externalReference);
        $this->assertEquals('Stripe via Envoix', $savedFinancialAccount->bankName);
    }

    /** @test */
    public function it_can_parse_financial_account_from_api_result(): void
    {
        $fixtureJson = file_get_contents(__DIR__.'/../Fixtures/finance-accounts/1_create_financial_account_response.json');
        $financialAccount = FinancialAccountMapper::fromJson($fixtureJson);
        $this->assertEquals('a32ff243-f681-4a4f-accd-832f48a7aebe', $financialAccount->financialAccountId);
    }

    /** @test */
    public function it_can_parse_financial_accounts_from_filter_request(): void
    {
        $fixtureJson = file_get_contents(__DIR__.'/../Fixtures/finance-accounts/2_get_financial_account_response.json');
        $accounts = FinancialAccountMapper::collectionFromJson($fixtureJson);
        $this->assertCount(1, $accounts);
    }

    /** @test */
    public function it_can_filter_financial_accounts_with_corrected_implementation(): void
    {
        // Mock für die korrigierte filter Methode
        $mockResponses = [
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__.'/../Fixtures/finance-accounts/3_filter_financial_account_response.json')),
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        // Test der korrigierten filter Methode
        $result = LexwareOffice::financialAccounts()->filter(new FinancialAccountQuery(externalReference: 'acct_1PveDdFOGdyMR8V0'));

        // Jetzt sollte das Ergebnis nicht leer sein
        $this->assertCount(1, $result, 'Filter sollte genau ein Konto zurückgeben');

        $account = $result->get(0);
        $this->assertNotNull($account);
        $this->assertEquals('acct_1PveDdFOGdyMR8V0', $account->externalReference);
        $this->assertEquals('Demo Account', $account->name);
    }
}
