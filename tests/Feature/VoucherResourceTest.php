<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Faker\Core\Uuid;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Pirabyte\LaravelLexwareOffice\Models\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class VoucherResourceTest extends TestCase
{
    private MockObject|LexwareOffice $clientMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock-Responses für die API-Aufrufe bei Personen-Kontakt
        $personMockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                "voucherId" => "ee143016-f177-4da7-a3b7-513a525a25a4",
                "externalReference" => "C205CD6E49F319AE9B03CAD01F555E2B9F188407"
            ])),
        ];

        $mock = new MockHandler($personMockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    /**
     * Lädt Fixture-Daten aus einer JSON-Datei.
     *
     * @param  string  $filename  Der Dateiname der Fixture (ohne Pfad/Endung)
     * @return array Die dekodierten JSON-Daten
     */
    private function loadFixture(string $filename): array
    {
        $path = __DIR__.'/../Fixtures/vouchers/'.$filename.'.json'; // Passe den Pfad an
        if (! file_exists($path)) {
            $this->fail('Fixture file not found: '.$path);
        }
        $content = file_get_contents($path);
        if ($content === false) {
            $this->fail('Could not read fixture file: '.$path);
        }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Error decoding JSON from fixture file: '.$path.' - '.json_last_error_msg());
        }

        return $data;
    }

    public function test_it_can_parse_voucher_from_api_result(): void
    {
        $fixtureData = $this->loadFixture('1_parse_voucher_from_lexware_office');
        $voucher = Voucher::fromArray($fixtureData);
        $this->assert_voucher_data($voucher, $fixtureData);
    }

    public function test_it_can_serialize_voucher_from_model(): void
    {
        $fixtureData = $this->loadFixture('1_parse_voucher_from_lexware_office');
        $voucher = Voucher::fromArray($fixtureData);
        $this->assertInstanceOf(Voucher::class, $voucher);

        $jsonArray = $voucher->jsonSerialize();

        $this->assertEquals($voucher->getId(), $jsonArray['id']);
        $this->assertEquals($voucher->getOrganizationId(), $jsonArray['organizationId']);
        $this->assertEquals($voucher->getType(), $jsonArray['type']);
        $this->assertEquals($voucher->getVoucherStatus(), $jsonArray['voucherStatus']);
        $this->assertEquals($voucher->getVoucherNumber(), $jsonArray['voucherNumber']);
        $this->assertEquals($voucher->getVoucherDate(), $jsonArray['voucherDate']);
        $this->assertEquals($voucher->getShippingDate(), $jsonArray['shippingDate']);
        $this->assertEquals($voucher->getDueDate(), $jsonArray['dueDate']);
        $this->assertEquals($voucher->getTotalGrossAmount(), $jsonArray['totalGrossAmount']);
        $this->assertEquals($voucher->getTotalTaxAmount(), $jsonArray['totalTaxAmount']);
        $this->assertEquals($voucher->getTaxType(), $jsonArray['taxType']);
        $this->assertEquals($voucher->getUseCollectiveContact(), $jsonArray['useCollectiveContact']);
        $this->assertEquals($voucher->getRemark(), $jsonArray['remark']);
        $this->assertEquals(count($voucher->getVoucherItems()), count($jsonArray['voucherItems']));
        $this->assertEquals(count($voucher->getFiles()), count($jsonArray['files']));
        $this->assertEquals($voucher->getCreatedDate(), $jsonArray['createdDate']);
        $this->assertEquals($voucher->getUpdatedDate(), $jsonArray['updatedDate']);
        $this->assertEquals($voucher->getVersion(), $jsonArray['version']);
    }

    public function test_it_can_serialize_null_values()
    {
        $voucher = new Voucher;
        $voucher->setTotalTaxAmount(0);
        $voucher->setTotalGrossAmount(0);

        $voucherItem = new VoucherItem;
        $voucherItem->setTaxRatePercent(0);
        $voucherItem->setAmount(0);
        $voucherItem->setTaxAmount(0);

        $voucher->setVoucherItems([
            $voucherItem,
        ]);

        $json = $voucher->jsonSerialize();
        $this->assertEquals($voucher->getTotalTaxAmount(), $json['totalTaxAmount']);

    }

    public function test_it_can_parse_vouchers_from_filter_request()
    {
        $fixtureData = $this->loadFixture('2_filter_voucher_response');
        foreach ($fixtureData['content'] as $fixtureVoucher) {
            $voucher = Voucher::fromArray($fixtureVoucher);
            $this->assert_voucher_data($voucher, $fixtureVoucher);
        }
    }

    public function test_it_can_create_voucher_assignments()
    {
        $hint = TransactionAssignmentHint::fromArray([
            'voucherId' => 'ee143016-f177-4da7-a3b7-513a525a25a4',
            'externalReference' => 'C205CD6E49F319AE9B03CAD01F555E2B9F188407'

        ]);
        $created = \Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice::transactionAssignmentHints()->create($hint);

        $this->assertEquals($hint->getVoucherId(), $created->getVoucherId());
        $this->assertEquals($hint->getExternalReference(), $created->getExternalReference());
    }

    private function assert_voucher_data(Voucher $voucher, array $fixtureData): void
    {
        $this->assertInstanceOf(Voucher::class, $voucher);

        $this->assertEquals($voucher->getId(), $fixtureData['id']);
        $this->assertEquals($voucher->getOrganizationId(), $fixtureData['organizationId']);
        $this->assertEquals($voucher->getType(), $fixtureData['type']);
        if (isset($fixtureData['voucherStatus'])) {
            $this->assertEquals($voucher->getVoucherStatus(), $fixtureData['voucherStatus']);
        }
        $this->assertEquals($voucher->getVoucherNumber(), $fixtureData['voucherNumber']);
        $this->assertEquals($voucher->getVoucherDate(), $fixtureData['voucherDate']);
        if (isset($fixtureData['shippingDate'])) {
            $this->assertEquals($voucher->getShippingDate(), $fixtureData['shippingDate']);
        }
        $this->assertEquals($voucher->getDueDate(), $fixtureData['dueDate']);
        $this->assertEquals($voucher->getTotalGrossAmount(), $fixtureData['totalGrossAmount']);
        $this->assertEquals($voucher->getTotalTaxAmount(), $fixtureData['totalTaxAmount']);
        $this->assertEquals($voucher->getTaxType(), $fixtureData['taxType']);
        $this->assertEquals($voucher->getUseCollectiveContact(), $fixtureData['useCollectiveContact']);
        $this->assertEquals($voucher->getRemark(), $fixtureData['remark']);

        $this->assertEquals(count($voucher->getVoucherItems()), count($fixtureData['voucherItems']));

        foreach ($fixtureData['voucherItems'] as $fixtureVoucherItem) {
            $voucherItem = VoucherItem::fromArray($fixtureVoucherItem);
            $this->assertInstanceOf(VoucherItem::class, $voucherItem);

            $this->assertEquals($voucherItem->getAmount(), $fixtureVoucherItem['amount']);
            $this->assertEquals($voucherItem->getTaxAmount(), $fixtureVoucherItem['taxAmount']);
            $this->assertEquals($voucherItem->getTaxRatePercent(), $fixtureVoucherItem['taxRatePercent']);
            $this->assertEquals($voucherItem->getCategoryId(), $fixtureVoucherItem['categoryId']);
        }

        $this->assertEquals(count($voucher->getFiles()), count($fixtureData['files']));
        $this->assertEquals($voucher->getCreatedDate(), $fixtureData['createdDate']);
        $this->assertEquals($voucher->getUpdatedDate(), $fixtureData['updatedDate']);
        $this->assertEquals($voucher->getVersion(), $fixtureData['version']);

        $this->assertEquals(count($voucher->jsonSerialize()), count($fixtureData));
    }

}
