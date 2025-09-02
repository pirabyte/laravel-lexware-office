<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\OptimisticLockingException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class OptimisticLockingTest extends TestCase
{
    public function test_contact_update_with_correct_version_succeeds()
    {
        // Mock successful update response
        $updateResponse = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 2,
        ];

        $getResponse = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'version' => 2,
            'person' => [
                'firstName' => 'John',
                'lastName' => 'Doe Updated',
            ],
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($updateResponse)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($getResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Create contact with version 1
        $contact = Contact::createPerson('John', 'Doe');
        $contact->setVersion(1);

        $updatedContact = $instance->contacts()->update('123e4567-e89b-12d3-a456-426614174000', $contact);

        $this->assertEquals(2, $updatedContact->getVersion());
        $this->assertEquals('Doe Updated', $updatedContact->getPerson()->getLastName());
    }

    public function test_contact_update_with_version_conflict_throws_optimistic_locking_exception()
    {
        // Create conflict response (409)
        $conflictResponse = [
            'message' => 'Version conflict',
            'currentVersion' => 3,
        ];

        $request = new Request('PUT', 'contacts/123');
        $response = new Response(409, [], json_encode($conflictResponse));
        $exception = new RequestException('Conflict', $request, $response);

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Create contact with version 1 (but server has version 3)
        $contact = Contact::createPerson('John', 'Doe');
        $contact->setVersion(1);

        $this->expectException(OptimisticLockingException::class);
        $this->expectExceptionMessage('Contact update failed due to version conflict');

        try {
            $instance->contacts()->update('123e4567-e89b-12d3-a456-426614174000', $contact);
        } catch (OptimisticLockingException $e) {
            // Verify exception details
            $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $e->getEntityId());
            $this->assertEquals(1, $e->getAttemptedVersion());
            $this->assertEquals(3, $e->getCurrentVersion());
            $this->assertTrue($e->isOptimisticLockingConflict());

            throw $e;
        }
    }

    public function test_voucher_update_with_version_conflict_throws_optimistic_locking_exception()
    {
        // Create conflict response (409)
        $conflictResponse = [
            'message' => 'Version conflict',
            'version' => 5,
        ];

        $request = new Request('PUT', 'vouchers/456');
        $response = new Response(409, [], json_encode($conflictResponse));
        $exception = new RequestException('Conflict', $request, $response);

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Create voucher with version 2 (but server has version 5)
        $voucher = new Voucher;
        $voucher->setVersion(2);

        $this->expectException(OptimisticLockingException::class);
        $this->expectExceptionMessage('Voucher update failed due to version conflict');

        try {
            $instance->vouchers()->update('456', $voucher);
        } catch (OptimisticLockingException $e) {
            // Verify exception details
            $this->assertEquals('456', $e->getEntityId());
            $this->assertEquals(2, $e->getAttemptedVersion());
            $this->assertEquals(5, $e->getCurrentVersion());

            throw $e;
        }
    }

    public function test_optimistic_locking_exception_provides_helpful_methods()
    {
        $exception = new OptimisticLockingException(
            'Test conflict',
            'entity-123',
            1,
            3
        );

        $this->assertTrue($exception->isOptimisticLockingConflict());
        $this->assertEquals('entity-123', $exception->getEntityId());
        $this->assertEquals(1, $exception->getAttemptedVersion());
        $this->assertEquals(3, $exception->getCurrentVersion());
        $this->assertEquals(409, $exception->getCode());

        $this->assertStringContainsString('refresh', $exception->getUserMessage());
        $this->assertStringContainsString('latest version', $exception->getRetryAction());
    }

    public function test_supports_optimistic_locking_trait_methods()
    {
        $contact = Contact::createPerson('John', 'Doe');
        $contact->setVersion(5);

        $this->assertTrue($contact->supportsOptimisticLocking());
        $this->assertEquals(5, $contact->getCurrentVersion());

        $contact->incrementVersion();
        $this->assertEquals(6, $contact->getCurrentVersion());

        $dataForUpdate = $contact->toArrayForUpdate();
        $this->assertEquals(6, $dataForUpdate['version']);
    }

    public function test_contact_update_includes_version_in_request_data()
    {
        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        // Mock successful response
        $updateResponse = ['id' => '123', 'version' => 2];
        $getResponse = [
            'id' => '123',
            'version' => 2,
            'person' => ['firstName' => 'John', 'lastName' => 'Doe'],
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($updateResponse)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($getResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        // Create contact with specific version
        $contact = Contact::createPerson('John', 'Doe');
        $contact->setVersion(1);

        $instance->contacts()->update('123', $contact);

        // Check that version was included in the request
        $this->assertCount(2, $container); // PUT request + GET request
        $putRequest = $container[0]['request'];
        $requestBody = json_decode($putRequest->getBody()->getContents(), true);

        $this->assertEquals(1, $requestBody['version']);
    }

    public function test_non_conflict_api_errors_are_re_thrown_unchanged()
    {
        // Create a non-conflict error (e.g., 400 Bad Request)
        $errorResponse = ['message' => 'Invalid data'];
        $request = new Request('PUT', 'contacts/123');
        $response = new Response(400, [], json_encode($errorResponse));
        $exception = new RequestException('Bad Request', $request, $response);

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');

        // Set mock client using reflection
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty('client');
        $reflectionProperty->setValue($instance, $client);

        $contact = Contact::createPerson('John', 'Doe');
        $contact->setVersion(1);

        // Should get the original LexwareOfficeApiException, not OptimisticLockingException
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(400);

        $instance->contacts()->update('123', $contact);
    }
}
