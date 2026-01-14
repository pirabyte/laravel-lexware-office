<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock-Responses für die API-Aufrufe bei Personen-Kontakt
        $personMockResponses = [
            // Response für create
            new Response(401, [
                'Content-Type' => 'application/json',
            ], json_encode([
                'message' => 'Unauthorized',
            ])),
        ];

        $mock = new MockHandler($personMockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    public function test_it_can_read_from_exception()
    {
        $this->assertThrows(
            function () {
                LexwareOffice::contacts()->get('something');
            },
            function (LexwareOfficeApiException $exception) {
                $this->assertInstanceOf(LexwareOfficeApiException::class, $exception);
                $this->assertEquals('Unauthorized', $exception->getMessage());
                $this->assertEquals(401, $exception->getStatusCode());
                $this->assertEquals(json_encode([
                    'message' => 'Unauthorized',
                ]), $exception->getError()->rawBody);

                return true;
            }
        );
    }
}
