<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\CreditCardProvider;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FinancialAccountTest extends TestCase
{
    public function test_it_can_get_financial_account_by_id(): void
    {
        // Mock-Response erstellen
        $accountData = [
            'financialAccountId' => '123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2023-02-21T00:00:00.000+01:00',
            'lastModifiedDate' => '2023-02-21T00:00:00.000+01:00',
            'lockVersion' => 1,
            'type' => 'GIRO',
            'accountSystem' => 'UNKNOWN',
            'name' => 'Girokonto',
            'bankName' => 'Test Bank',
            'accountHolder' => 'Max Mustermann',
            'balance' => 1000.00,
            'iban' => 'DE12345678901234567890',
            'bic' => 'TESTBICXXXXX',
            'deactivated' => false,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($accountData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Finanzkonto abrufen
        $account = $instance->financialAccounts()->get('123e4567-e89b-12d3-a456-426614174000');

        // Assertions
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $account->getFinancialAccountId());
        $this->assertEquals('2023-02-21T00:00:00.000+01:00', $account->getCreatedDate());
        $this->assertEquals('2023-02-21T00:00:00.000+01:00', $account->getLastModifiedDate());
        $this->assertEquals(1, $account->getLockVersion());
        $this->assertEquals(FinancialAccountType::GIRO, $account->getType());
        $this->assertEquals(AccountSystem::UNKNOWN, $account->getAccountSystem());
        $this->assertEquals('Girokonto', $account->getName());
        $this->assertEquals('Test Bank', $account->getBankName());
        $this->assertEquals('Max Mustermann', $account->getAccountHolder());
        $this->assertEquals(1000.00, $account->getBalance());
        $this->assertEquals('DE12345678901234567890', $account->getIban());
        $this->assertEquals('TESTBICXXXXX', $account->getBic());
        $this->assertFalse($account->isDeactivated());
    }

    public function test_it_can_filter_financial_accounts(): void
    {
        // Mock-Response erstellen
        $accountsData = [
            'content' => [
                [
                    'financialAccountId' => '123e4567-e89b-12d3-a456-426614174000',
                    'type' => 'GIRO',
                    'accountSystem' => 'UNKNOWN',
                    'name' => 'Girokonto',
                    'balance' => 1000.00,
                    'deactivated' => false,
                ],
                [
                    'financialAccountId' => '223e4567-e89b-12d3-a456-426614174001',
                    'type' => 'CREDITCARD',
                    'accountSystem' => 'UNKNOWN',
                    'name' => 'Kreditkarte',
                    'balance' => 500.00,
                    'provider' => 'VISA',
                    'creditCardNumber' => '************1234',
                    'deactivated' => false,
                ],
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 2,
            'totalPages' => 1,
            'numberOfElements' => 2,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($accountsData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Finanzkonten filtern
        $accounts = $instance->financialAccounts()->filter([
            'type' => 'GIRO',
            'deactivated' => false,
        ]);

        // Assertions
        $this->assertCount(2, $accounts);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $accounts[0]->getFinancialAccountId());
        $this->assertEquals(FinancialAccountType::GIRO, $accounts[0]->getType());
        $this->assertEquals('Girokonto', $accounts[0]->getName());
        $this->assertEquals(1000.00, $accounts[0]->getBalance());

        $this->assertEquals('223e4567-e89b-12d3-a456-426614174001', $accounts[1]->getFinancialAccountId());
        $this->assertEquals(FinancialAccountType::CREDITCARD, $accounts[1]->getType());
        $this->assertEquals('Kreditkarte', $accounts[1]->getName());
        $this->assertEquals(500.00, $accounts[1]->getBalance());
        $this->assertEquals(CreditCardProvider::VISA, $accounts[1]->getProvider());
        $this->assertEquals('************1234', $accounts[1]->getCreditCardNumber());
    }

    public function test_it_can_handle_empty_filter_values(): void
    {
        // Mock-Response erstellen
        $accountsData = [
            'content' => [],
            'page' => 0,
            'size' => 25,
            'totalElements' => 0,
            'totalPages' => 0,
            'numberOfElements' => 0,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($accountsData)),
        ]);

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Filter mit leeren Werten erstellen
        $instance->financialAccounts()->filter([
            'type' => 'GIRO',
            'deactivated' => null,
            'page' => '',
            'size' => 25,
        ]);

        // Assertions - Prüfen des tatsächlich gesendeten Requests
        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $query = \GuzzleHttp\Psr7\Query::parse($request->getUri()->getQuery());

        // Nur nicht-leere Filter sollten gesendet werden
        $this->assertArrayHasKey('type', $query);
        $this->assertArrayHasKey('size', $query);
        $this->assertArrayNotHasKey('deactivated', $query);
        $this->assertArrayNotHasKey('page', $query);

        // Werte prüfen
        $this->assertEquals('GIRO', $query['type']);
        $this->assertEquals('25', $query['size']);
    }

    /** @test */
    public function it_can_delete_financial_account(): void
    {
        // Mock für erfolgreiche Löschung (204 No Content)
        $mock = new MockHandler([
            new Response(204),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Finanzkonto löschen
        $result = $instance->financialAccounts()->delete('123e4567-e89b-12d3-a456-426614174000');

        // Bei erfolgreicher Löschung wird true zurückgegeben
        $this->assertTrue($result);
    }

    public function test_it_throws_exception_if_financial_account_has_transactions(): void
    {
        // Mock für Fehler 406 (Not Acceptable)
        $mock = new MockHandler([
            new Response(406, [], json_encode([
                'error' => 'Cannot delete financial account with assigned transactions',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Exception erwartet
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(406);

        // Versuch, ein Finanzkonto mit zugewiesenen Transaktionen zu löschen
        $instance->financialAccounts()->delete('123e4567-e89b-12d3-a456-426614174000');
    }
}
