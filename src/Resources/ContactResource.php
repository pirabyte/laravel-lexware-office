<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Generator;
use Pirabyte\LaravelLexwareOffice\Collections\Page;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\Contact;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactQuery;
use Pirabyte\LaravelLexwareOffice\Dto\Contacts\ContactWrite;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\OptimisticLockingException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Http\QueryParams;
use Pirabyte\LaravelLexwareOffice\Mappers\Contacts\ContactMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Contacts\ContactPageMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Contacts\ContactWriteMapper;

class ContactResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function create(ContactWrite $contact): Contact
    {
        $body = ContactWriteMapper::toJsonBody($contact);
        $response = $this->http->postJson('contacts', $body);

        $decoded = JsonCodec::decode($response->body);
        if (! array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            $id = $decoded['id'] ?? null;
            if (is_string($id) && $id !== '') {
                return $this->get($id);
            }
        }

        return ContactMapper::fromJson($response->body);
    }

    public function get(string $id): Contact
    {
        $response = $this->http->get("contacts/{$id}");

        return ContactMapper::fromJson($response->body);
    }

    public function update(string $id, ContactWrite $contact): Contact
    {
        if ($contact->version === null) {
            throw new \InvalidArgumentException('ContactWrite.version is required for updates (optimistic locking)');
        }

        $body = ContactWriteMapper::toJsonBody($contact);
        try {
            $response = $this->http->putJson("contacts/{$id}", $body);
        } catch (LexwareOfficeApiException $e) {
            if ($e->isConflictError()) {
                throw new OptimisticLockingException(
                    'Contact update failed due to version conflict',
                    $id,
                    $contact->version,
                    self::extractVersionFromErrorResponse($e),
                    $e
                );
            }

            throw $e;
        }

        $decoded = JsonCodec::decode($response->body);
        if (! array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            $returnedId = $decoded['id'] ?? null;
            if (is_string($returnedId) && $returnedId !== '') {
                return $this->get($returnedId);
            }
        }

        return ContactMapper::fromJson($response->body);
    }

    private static function extractVersionFromErrorResponse(LexwareOfficeApiException $e): ?int
    {
        $responseData = json_decode($e->getError()->rawBody, true);
        if (! is_array($responseData)) {
            return null;
        }

        return $responseData['currentVersion']
            ?? $responseData['version']
            ?? $responseData['lockVersion']
            ?? null;
    }

    /**
     * @return Page<Contact>
     */
    public function filter(ContactQuery $filters = new ContactQuery()): Page
    {
        $query = QueryParams::empty()
            ->with('page', $filters->page)
            ->with('size', $filters->size);

        if ($filters->customer !== null) {
            $query = $query->with('customer', $filters->customer);
        }
        if ($filters->vendor !== null) {
            $query = $query->with('vendor', $filters->vendor);
        }
        if ($filters->name !== null && $filters->name !== '') {
            $query = $query->with('name', $filters->name);
        }
        if ($filters->email !== null && $filters->email !== '') {
            $query = $query->with('email', $filters->email);
        }
        if ($filters->number !== null && $filters->number !== '') {
            $query = $query->with('number', $filters->number);
        }

        $response = $this->http->get('contacts', $query);

        return ContactPageMapper::fromJson($response->body);
    }

    /**
     * @return Page<Contact>
     */
    public function all(int $page = 0, int $size = 25): Page
    {
        $query = QueryParams::empty()
            ->with('page', $page)
            ->with('size', min($size, 250));

        $response = $this->http->get('contacts', $query);

        return ContactPageMapper::fromJson($response->body);
    }

    /**
     * Returns the total number of contacts
     *
     * @throws LexwareOfficeApiException
     */
    public function count(): int
    {
        $page = $this->all(0, 1);

        return $page->pageInfo->totalElements;
    }

    /**
     * @return Generator<Contact>
     */
    public function getAutoPagingIterator(ContactQuery $filters = new ContactQuery(size: 25)): Generator
    {
        $page = $filters->page;
        while (true) {
            $current = new ContactQuery(
                customer: $filters->customer,
                vendor: $filters->vendor,
                name: $filters->name,
                email: $filters->email,
                number: $filters->number,
                page: $page,
                size: $filters->size,
            );

            $result = $this->filter($current);

            foreach ($result->items as $contact) {
                yield $contact;
            }

            if ($result->pageInfo->last) {
                break;
            }

            $page++;
        }
    }
}
