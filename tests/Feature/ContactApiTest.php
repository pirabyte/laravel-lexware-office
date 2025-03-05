<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

/**
 * @method assertEquals(string $string, string $getId)
 */
class ContactApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock-Responses für die API-Aufrufe
        $mockResponses = [
            // Response für create
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'resourceUri' => 'https://api.lexoffice.io/v1/contacts/123e4567-e89b-12d3-a456-426614174000'
            ])),
            // Response für get (innerhalb von create)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'version' => 1,
                'roles' => [
                    'customer' => ['number' => 'K-12345']
                ],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann'
                ]
            ])),
            // Response für den expliziten get-Aufruf im Test
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'version' => 1,
                'roles' => [
                    'customer' => ['number' => 'K-12345']
                ],
                'person' => [
                    'salutation' => 'Herr',
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann'
                ]
            ]))
        ];

        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    /** @test */
    public function it_can_create_and_retrieve_a_contact(): void
    {
        // Kontakt erstellen
        $contact = LexwareOffice::contacts()->create(Contact::fromArray([
            'version' => 0,
            'roles' => [
                'customer' => ['number' => 'K-12345']
            ],
            'person' => [
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann'
            ]
        ]));

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $contact->getId());

        // Kontakt abrufen
        $retrievedContact = LexwareOffice::contacts()->get($contact->getId());

        $this->assertEquals('Herr', $retrievedContact->getPerson()->getSalutation());
        $this->assertEquals('Max', $retrievedContact->getPerson()->getFirstName());
        $this->assertEquals('Mustermann', $retrievedContact->getPerson()->getLastName());
        $this->assertEquals('K-12345', $retrievedContact->getRoles()['customer']['number']);
    }
}