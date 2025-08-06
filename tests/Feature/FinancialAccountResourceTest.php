<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\FinancialAccount;
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
        $financialAccount = new FinancialAccount(
            'a32ff243-f681-4a4f-accd-832f48a7aebe',
            FinancialAccountType::PAYMENT_PROVIDER,
            AccountSystem::PAYMENT_PROVIDER,
            'Stripe (Demo Company 123)'
        );

        $savedFinancialAccount = LexwareOffice::financialAccounts()->create($financialAccount);

        $this->assertEquals('a32ff243-f681-4a4f-accd-832f48a7aebe', $savedFinancialAccount->getFinancialAccountId());
        $this->assertEquals('Stripe (Demo Company 123)', $savedFinancialAccount->getName());
        $this->assertEquals(FinancialAccountType::PAYMENT_PROVIDER, $savedFinancialAccount->getType());
        $this->assertEquals('acct_1PiC0uRqvWc6M8Pc', $savedFinancialAccount->getExternalReference());
        $this->assertEquals('Stripe via Envoix', $savedFinancialAccount->getBankName());
    }

    /** @test */
    public function it_can_parse_financial_account_from_api_result(): void
    {
        $fixtureData = json_decode(file_get_contents(__DIR__.'/../Fixtures/finance-accounts/1_create_financial_account_response.json'), true);
        $financialAccount = FinancialAccount::fromArray($fixtureData);
        $this->assert_financial_account_data($financialAccount, $fixtureData);
    }

    /** @test */
    public function it_can_parse_financial_accounts_from_filter_request(): void
    {
        $fixtureData = json_decode(file_get_contents(__DIR__.'/../Fixtures/finance-accounts/2_get_financial_account_response.json'), true);
        foreach ($fixtureData as $fixtureFinancialAccount) {
            $financialAccount = FinancialAccount::fromArray($fixtureFinancialAccount);
            $this->assert_financial_account_data($financialAccount, $fixtureFinancialAccount);
        }
    }

    private function assert_financial_account_data(FinancialAccount $financialAccount, array $fixtureData): void
    {
        $this->assertInstanceOf(FinancialAccount::class, $financialAccount);

        $this->assertEquals($financialAccount->getFinancialAccountId(), $fixtureData['financialAccountId']);
        $this->assertEquals($financialAccount->getType()->value, $fixtureData['type']);
        $this->assertEquals($financialAccount->getName(), $fixtureData['name']);
        $this->assertEquals($financialAccount->getBankName(), $fixtureData['bankName']);
        $this->assertEquals($financialAccount->getLockVersion(), $fixtureData['lockVersion']);
        $this->assertEquals($financialAccount->getAccountSystem()->value, $fixtureData['accountSystem']);
        $this->assertEquals($financialAccount->isDeactivated(), $fixtureData['deactivated']);
    }
}
