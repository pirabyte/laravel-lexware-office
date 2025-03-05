<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class RateLimitTest extends TestCase
{
    /** @test */
    public function it_respects_rate_limit()
    {
        // Mock RateLimiter Facade
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                // Überprüfen, ob der korrekte Schlüssel und die richtigen maximalen Versuche verwendet werden
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false); // Simuliere, dass das Limit noch nicht erreicht wurde

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                // Überprüfen, ob der korrekte Schlüssel und der richtige Verfall verwendet werden
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1); // Rückgabe der Anzahl der Hits

        // Mock HTTP response
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '123']))
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

        // Anfrage senden
        $response = $instance->get('contacts/123');

        // Überprüfen, ob die Antwort korrekt ist
        $this->assertEquals(['id' => '123'], $response);
    }

    /** @test */
    public function it_throws_exception_when_rate_limit_is_reached()
    {
        // Mock RateLimiter Facade
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(true); // Simuliere, dass das Limit erreicht wurde

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(30); // Simuliere, dass der nächste Versuch in 30 Sekunden möglich ist

        // Mock HTTP response (sollte nicht aufgerufen werden)
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '123']))
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

        // Erwarten, dass eine Exception geworfen wird
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage("Rate limit erreicht. Bitte warten Sie 30 Sekunden.");
        $this->expectExceptionCode(429);

        // Anfrage senden (sollte Exception werfen)
        $instance->get('contacts/123');
    }

    /** @test */
    public function it_allows_setting_custom_rate_limit()
    {
        // Mock RateLimiter Facade
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                // Überprüfen, ob der korrekte Schlüssel und die richtigen maximalen Versuche verwendet werden
                return $key === 'lexware_office_api' && $maxAttempts === 10; // Wir setzen das Limit auf 10
            })
            ->andReturn(false); // Simuliere, dass das Limit noch nicht erreicht wurde

        RateLimiter::shouldReceive('hit')
            ->once()
            ->andReturn(1); // Rückgabe der Anzahl der Hits

        // Mock HTTP response
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '123']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // LexwareOffice-Client erstellen
        /* @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Rate-Limit auf 10 Anfragen pro Minute setzen
        $instance->setRateLimit(10);

        // Methode setClient ist geschützt, also nutzen wir Reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Anfrage senden
        $response = $instance->get('contacts/123');

        // Überprüfen, ob die Antwort korrekt ist
        $this->assertEquals(['id' => '123'], $response);
    }
    
    /** @test */
    public function it_applies_rate_limit_to_post_requests()
    {
        // Mock RateLimiter Facade
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1);

        // Mock HTTP response
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['id' => '123']))
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

        // POST-Anfrage senden
        $response = $instance->post('contacts', ['name' => 'Test Contact']);

        // Überprüfen, ob die Antwort korrekt ist
        $this->assertEquals(['id' => '123'], $response);
    }
    
    /** @test */
    public function it_applies_rate_limit_to_put_requests()
    {
        // Mock RateLimiter Facade
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->withArgs(function ($key, $maxAttempts) {
                return $key === 'lexware_office_api' && $maxAttempts === 50;
            })
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->withArgs(function ($key, $decay) {
                return $key === 'lexware_office_api' && $decay === 60;
            })
            ->andReturn(1);

        // Mock HTTP response
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '123']))
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

        // PUT-Anfrage senden
        $response = $instance->put('contacts/123', ['name' => 'Updated Contact']);

        // Überprüfen, ob die Antwort korrekt ist
        $this->assertEquals(['id' => '123'], $response);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}