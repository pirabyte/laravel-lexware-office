<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherItemCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\Voucher;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherDocument;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherWrite;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Mappers\Vouchers\VoucherMapper;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class VoucherResourceTest extends TestCase
{
    public function test_it_can_parse_voucher_from_api_result(): void
    {
        $fixtureJson = file_get_contents(__DIR__.'/../Fixtures/vouchers/1_parse_voucher_from_lexware_office.json');
        $voucher = VoucherMapper::fromJson($fixtureJson);

        $this->assertInstanceOf(Voucher::class, $voucher);
        $this->assertEquals('66196c43-baf3-4335-bfee-d610367059db', $voucher->id);
        $this->assertEquals(1, $voucher->version);
        $this->assertCount(1, $voucher->voucherItems);
        $this->assertCount(0, $voucher->files);
    }

    public function test_it_can_filter_vouchers(): void
    {
        $fixtureJson = file_get_contents(__DIR__.'/../Fixtures/vouchers/2_filter_voucher_response.json');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $fixtureJson),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $page = LexwareOffice::vouchers()->filter('RE-001');

        $this->assertCount(2, $page->items);
        $this->assertEquals(2, $page->pageInfo->totalElements);

        $first = $page->items->get(0);
        $this->assertNotNull($first);
        $this->assertEquals('dba9418a-2381-48cd-afa3-81c0c1d0e53e', $first->id);
    }

    public function test_it_can_generate_document(): void
    {
        $documentData = [
            'fileId' => 'file123',
            'fileName' => 'invoice_123.pdf',
            'mimeType' => 'application/pdf',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($documentData)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $result = LexwareOffice::vouchers()->document('voucher_123');

        $this->assertInstanceOf(VoucherDocument::class, $result);
        $this->assertEquals('file123', $result->fileId);
        $this->assertEquals('invoice_123.pdf', $result->fileName);
        $this->assertEquals('application/pdf', $result->mimeType);
    }

    public function test_it_can_create_a_voucher_and_fetch_details(): void
    {
        $voucherFixtureJson = file_get_contents(__DIR__.'/../Fixtures/vouchers/1_parse_voucher_from_lexware_office.json');

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['id' => '66196c43-baf3-4335-bfee-d610367059db'])),
            new Response(200, ['Content-Type' => 'application/json'], $voucherFixtureJson),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $instance = app('lexware-office');
        $instance->setClient($client);

        $voucherWrite = new VoucherWrite(
            type: 'salesinvoice',
            voucherDate: new DateTimeImmutable('2023-06-28T00:00:00.000+02:00'),
            totalGrossAmount: 119,
            totalTaxAmount: 19.0,
            taxType: 'gross',
            useCollectiveContact: true,
            voucherItems: VoucherItemCollection::empty()->with(new VoucherItem(
                amount: 119,
                taxAmount: 19.0,
                taxRatePercent: 19,
                categoryId: '8f8664a8-fd86-11e1-a21f-0800200c9a66',
            )),
        );

        $created = LexwareOffice::vouchers()->create($voucherWrite);

        $this->assertInstanceOf(Voucher::class, $created);
        $this->assertEquals('66196c43-baf3-4335-bfee-d610367059db', $created->id);
    }
}


