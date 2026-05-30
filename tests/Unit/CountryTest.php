<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\TaxClassification;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class CountryTest extends TestCase
{
    /** @test */
    public function it_can_get_all_countries(): void
    {
        // Mock-Response erstellen
        $countriesData = [
            [
                'countryCode' => 'DE',
                'countryNameDE' => 'Deutschland',
                'countryNameEN' => 'Germany',
                'taxClassification' => 'de',
            ],
            [
                'countryCode' => 'FR',
                'countryNameDE' => 'Frankreich',
                'countryNameEN' => 'France',
                'taxClassification' => 'intraCommunity',
            ],
            [
                'countryCode' => 'US',
                'countryNameDE' => 'Vereinigte Staaten von Amerika',
                'countryNameEN' => 'United States',
                'taxClassification' => 'thirdPartyCountry',
            ],
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($countriesData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');
        $instance->setClient($client);

        // Länder abrufen
        $countries = $instance->countries()->all();

        // Assertions
        $this->assertCount(3, $countries);

        // Deutschland prüfen
        $de = $countries->get(0);
        $this->assertNotNull($de);
        $this->assertEquals('DE', $de->countryCode);
        $this->assertEquals('Deutschland', $de->countryNameDE);
        $this->assertEquals('Germany', $de->countryNameEN);
        $this->assertEquals(TaxClassification::GERMANY, $de->taxClassification);

        // Frankreich prüfen
        $fr = $countries->get(1);
        $this->assertNotNull($fr);
        $this->assertEquals('FR', $fr->countryCode);
        $this->assertEquals('Frankreich', $fr->countryNameDE);
        $this->assertEquals('France', $fr->countryNameEN);
        $this->assertEquals(TaxClassification::INTRA_COMMUNITY, $fr->taxClassification);

        // USA prüfen
        $us = $countries->get(2);
        $this->assertNotNull($us);
        $this->assertEquals('US', $us->countryCode);
        $this->assertEquals('Vereinigte Staaten von Amerika', $us->countryNameDE);
        $this->assertEquals('United States', $us->countryNameEN);
        $this->assertEquals(TaxClassification::THIRD_PARTY_COUNTRY, $us->taxClassification);
    }
}
