<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Pirabyte\LaravelLexwareOffice\Models\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

/**
 * @property $app
 *
 * @method assertEquals(string $string, string $getId)
 * @method assertArrayHasKey(string $string, array $result)
 * @method assertCount(int $int, mixed $content)
 * @method assertIsArray(array $result)
 */
class VoucherTest extends TestCase
{
    /** @test */
    public function it_can_create_a_voucher(): void
    {
        // Mock-Response erstellen
        $responseData = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 0,
            'resourceUri' => 'https://api.lexoffice.io/v1/vouchers/123e4567-e89b-12d3-a456-426614174000',
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
            'updatedDate' => '2020-01-01T00:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($responseData)),
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

        // Voucher-Daten
        $voucherItem = VoucherItem::fromArray([
            'type' => 'custom',
            'name' => 'Test-Produkt',
            'description' => 'Beschreibung des Produkts',
            'quantity' => 1,
            'unitName' => 'Stück',
            'unitPrice' => [
                'currency' => 'EUR',
                'netAmount' => 100.00,
                'taxRatePercentage' => 19,
            ],
            'vatRateType' => 'normal',
        ]);

        $voucher = Voucher::fromArray([
            'version' => 0,
            'type' => 'salesinvoice',
            'voucherDate' => '2020-01-01',
            'address' => [
                'name' => 'Max Mustermann',
                'street' => 'Musterstraße 1',
                'zip' => '12345',
                'city' => 'Musterstadt',
                'countryCode' => 'DE',
            ],
            'voucherItems' => [$voucherItem->jsonSerialize()],
        ]);

        // API aufrufen
        $createdVoucher = $instance->vouchers()->create($voucher);

        // Assertions
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $createdVoucher->getId());
    }

    /** @test */
    public function it_can_filter_vouchers(): void
    {
        // Mock-Response erstellen
        $vouchersData = [
            'content' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'version' => 0,
                    'type' => 'salesinvoice',
                    'voucherNumber' => 'RE-001',
                    'voucherDate' => '2020-01-01',
                    'totalAmount' => [
                        'currency' => 'EUR',
                        'totalNetAmount' => 100.00,
                        'totalGrossAmount' => 119.00,
                        'totalTaxAmount' => 19.00,
                    ],
                ],
                [
                    'id' => '223e4567-e89b-12d3-a456-426614174001',
                    'version' => 0,
                    'type' => 'salesinvoice',
                    'voucherNumber' => 'RE-002',
                    'voucherDate' => '2020-01-02',
                    'totalAmount' => [
                        'currency' => 'EUR',
                        'totalNetAmount' => 200.00,
                        'totalGrossAmount' => 238.00,
                        'totalTaxAmount' => 38.00,
                    ],
                ],
            ],
            'page' => 0,
            'size' => 25,
            'totalElements' => 2,
            'totalPages' => 1,
            'numberOfElements' => 2,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($vouchersData)),
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

        // Belege filtern
        $result = $instance->vouchers()->filter('RE-001');

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['content']);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $result['content'][0]->getId());
        $this->assertEquals('223e4567-e89b-12d3-a456-426614174001', $result['content'][1]->getId());
        $this->assertEquals(0, $result['pagination']['page']);
        $this->assertEquals(25, $result['pagination']['size']);
        $this->assertEquals(1, $result['pagination']['totalPages']);
        $this->assertEquals(2, $result['pagination']['totalElements']);
    }

    /** @test */
    public function it_can_generate_document(): void
    {
        // Mock-Response erstellen
        $documentData = [
            'id' => 'file123',
            'fileName' => 'invoice_123.pdf',
            'mimeType' => 'application/pdf',
            'size' => 12345,
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($documentData)),
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

        // Dokument generieren
        $result = $instance->vouchers()->document('123e4567-e89b-12d3-a456-426614174000');

        // Assertions
        $this->assertIsArray($result);
        $this->assertEquals('file123', $result['id']);
        $this->assertEquals('invoice_123.pdf', $result['fileName']);
        $this->assertEquals('application/pdf', $result['mimeType']);
    }

    /** @test */
    public function it_can_attach_file_to_voucher(): void
    {
        // Mock-Response erstellen
        $fileData = [
            'id' => 'file456',
            'fileName' => 'test_document.pdf',
            'mimeType' => 'application/pdf',
            'size' => 54321,
            'createdDate' => '2020-01-01T00:00:00.000+01:00',
        ];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode($fileData)),
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

        // Test-Datei als Stream erstellen
        $fileContent = 'Test PDF Inhalt';
        $stream = Utils::streamFor($fileContent);

        // Datei an Beleg anhängen
        $result = $instance->vouchers()->attachFile(
            '123e4567-e89b-12d3-a456-426614174000',
            $stream,
            'test_document.pdf',
            'voucher'
        );

        // Assertions
        $this->assertIsArray($result);
        $this->assertEquals('file456', $result['id']);
        $this->assertEquals('test_document.pdf', $result['fileName']);
        $this->assertEquals('application/pdf', $result['mimeType']);
        $this->assertEquals(54321, $result['size']);
    }
}
