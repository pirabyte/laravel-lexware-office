<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\EmailAddressCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Contacts\PhoneNumberCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherItemCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactAddresses;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactRoles;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactWrite;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Person;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherWrite;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\OptimisticLockingException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class OptimisticLockingTest extends TestCase
{
    public function test_contact_update_with_correct_version_succeeds(): void
    {
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

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');
        $instance->setClient($client);

        $contact = $this->makeContactWrite(version: 1, lastName: 'Doe');

        $updatedContact = $instance->contacts()->update('123e4567-e89b-12d3-a456-426614174000', $contact);

        $this->assertEquals(2, $updatedContact->version);
        $this->assertEquals('Doe Updated', $updatedContact->person?->lastName);
    }

    public function test_contact_update_with_version_conflict_throws_optimistic_locking_exception(): void
    {
        $conflictResponse = [
            'message' => 'Version conflict',
            'currentVersion' => 3,
        ];

        $request = new Request('PUT', 'contacts/123');
        $response = new Response(409, [], json_encode($conflictResponse));
        $exception = new RequestException('Conflict', $request, $response);

        $mock = new MockHandler([$exception]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');
        $instance->setClient($client);

        $contact = $this->makeContactWrite(version: 1, lastName: 'Doe');

        $this->expectException(OptimisticLockingException::class);
        $this->expectExceptionMessage('Contact update failed due to version conflict');

        try {
            $instance->contacts()->update('123e4567-e89b-12d3-a456-426614174000', $contact);
        } catch (OptimisticLockingException $e) {
            $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $e->getEntityId());
            $this->assertEquals(1, $e->getAttemptedVersion());
            $this->assertEquals(3, $e->getCurrentVersion());
            $this->assertTrue($e->isOptimisticLockingConflict());
            throw $e;
        }
    }

    public function test_voucher_update_with_version_conflict_throws_optimistic_locking_exception(): void
    {
        $conflictResponse = [
            'message' => 'Version conflict',
            'version' => 5,
        ];

        $request = new Request('PUT', 'vouchers/456');
        $response = new Response(409, [], json_encode($conflictResponse));
        $exception = new RequestException('Conflict', $request, $response);

        $mock = new MockHandler([$exception]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');
        $instance->setClient($client);

        $voucher = $this->makeVoucherWrite(version: 2);

        $this->expectException(OptimisticLockingException::class);
        $this->expectExceptionMessage('Voucher update failed due to version conflict');

        try {
            $instance->vouchers()->update('456', $voucher);
        } catch (OptimisticLockingException $e) {
            $this->assertEquals('456', $e->getEntityId());
            $this->assertEquals(2, $e->getAttemptedVersion());
            $this->assertEquals(5, $e->getCurrentVersion());
            throw $e;
        }
    }

    public function test_optimistic_locking_exception_provides_helpful_methods(): void
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

    public function test_contact_update_includes_version_in_request_data(): void
    {
        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

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
        $instance->setClient($client);

        $contact = $this->makeContactWrite(version: 1, lastName: 'Doe');

        $instance->contacts()->update('123', $contact);

        $this->assertCount(2, $container); // PUT + GET
        $putRequest = $container[0]['request'];
        $requestBody = json_decode($putRequest->getBody()->getContents(), true);

        $this->assertEquals(1, $requestBody['version']);
    }

    public function test_non_conflict_api_errors_are_re_thrown_unchanged(): void
    {
        $errorResponse = ['message' => 'Invalid data'];
        $request = new Request('PUT', 'contacts/123');
        $response = new Response(400, [], json_encode($errorResponse));
        $exception = new RequestException('Bad Request', $request, $response);

        $mock = new MockHandler([$exception]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        /** @var LexwareOffice $instance */
        $instance = $this->app->make('lexware-office');
        $instance->setClient($client);

        $contact = $this->makeContactWrite(version: 1, lastName: 'Doe');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(400);

        $instance->contacts()->update('123', $contact);
    }

    private function makeContactWrite(int $version, string $lastName): ContactWrite
    {
        return new ContactWrite(
            roles: new ContactRoles(customer: null, vendor: null),
            person: new Person(salutation: null, firstName: 'John', lastName: $lastName),
            company: null,
            note: null,
            addresses: new ContactAddresses(billing: null, shipping: null),
            emailAddresses: EmailAddressCollection::empty(),
            phoneNumbers: PhoneNumberCollection::empty(),
            version: $version,
        );
    }

    private function makeVoucherWrite(int $version): VoucherWrite
    {
        $items = VoucherItemCollection::empty()->with(new VoucherItem(
            amount: 119,
            taxAmount: 19.0,
            taxRatePercent: 19,
            categoryId: '8f8664a8-fd86-11e1-a21f-0800200c9a66',
        ));

        return new VoucherWrite(
            type: 'salesinvoice',
            voucherDate: new DateTimeImmutable('2023-06-28T00:00:00.000+02:00'),
            totalGrossAmount: 119,
            totalTaxAmount: 19.0,
            taxType: 'gross',
            useCollectiveContact: true,
            voucherItems: $items,
            version: $version,
        );
    }
}


