<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\TaxClassification;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Country;
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
                'taxClassification' => 'de'
            ],
            [
                'countryCode' => 'FR',
                'countryNameDE' => 'Frankreich',
                'countryNameEN' => 'France',
                'taxClassification' => 'intraCommunity'
            ],
            [
                'countryCode' => 'US',
                'countryNameDE' => 'Vereinigte Staaten von Amerika',
                'countryNameEN' => 'United States',
                'taxClassification' => 'thirdPartyCountry'
            ]
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($countriesData))
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

        // Länder abrufen
        $countries = $instance->countries()->all();

        // Assertions
        $this->assertCount(3, $countries);
        
        // Deutschland prüfen
        $this->assertEquals('DE', $countries[0]->getCountryCode());
        $this->assertEquals('Deutschland', $countries[0]->getCountryNameDE());
        $this->assertEquals('Germany', $countries[0]->getCountryNameEN());
        $this->assertEquals(TaxClassification::GERMANY, $countries[0]->getTaxClassification());
        
        // Frankreich prüfen
        $this->assertEquals('FR', $countries[1]->getCountryCode());
        $this->assertEquals('Frankreich', $countries[1]->getCountryNameDE());
        $this->assertEquals('France', $countries[1]->getCountryNameEN());
        $this->assertEquals(TaxClassification::INTRA_COMMUNITY, $countries[1]->getTaxClassification());
        
        // USA prüfen
        $this->assertEquals('US', $countries[2]->getCountryCode());
        $this->assertEquals('Vereinigte Staaten von Amerika', $countries[2]->getCountryNameDE());
        $this->assertEquals('United States', $countries[2]->getCountryNameEN());
        $this->assertEquals(TaxClassification::THIRD_PARTY_COUNTRY, $countries[2]->getTaxClassification());
    }
}