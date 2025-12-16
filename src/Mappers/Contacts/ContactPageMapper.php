<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Contacts;

use Pirabyte\LaravelLexwareOffice\Collections\Contacts\ContactCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Page;
use Pirabyte\LaravelLexwareOffice\Collections\PageInfo;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class ContactPageMapper implements ApiMapper
{
    /**
     * @return Page<\Pirabyte\LaravelLexwareOffice\Dto\Contacts\Contact>
     */
    public static function fromJson(string $rawJson): Page
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for paginated contacts', $rawJson);
        }

        /** @var array<string, mixed> $data */
        $content = Assert::array($data['content'] ?? [], 'ContactPage.content must be a list');
        if (! array_is_list($content)) {
            throw new DecodeException('ContactPage.content must be a list', $rawJson);
        }

        $contacts = ContactCollection::empty();
        foreach ($content as $row) {
            $row = Assert::array($row, 'ContactPage.content item must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('ContactPage.content item must be an object', $rawJson);
            }

            /** @var array<string, mixed> $row */
            $contacts = $contacts->with(ContactMapper::fromArray($row, $rawJson));
        }

        $pageInfo = new PageInfo(
            page: Assert::int($data['number'] ?? 0, 'ContactPage.number must be int'),
            size: Assert::int($data['size'] ?? 0, 'ContactPage.size must be int'),
            first: Assert::bool($data['first'] ?? false, 'ContactPage.first must be bool'),
            last: Assert::bool($data['last'] ?? false, 'ContactPage.last must be bool'),
            totalPages: Assert::int($data['totalPages'] ?? 0, 'ContactPage.totalPages must be int'),
            totalElements: Assert::int($data['totalElements'] ?? 0, 'ContactPage.totalElements must be int'),
            numberOfElements: Assert::int($data['numberOfElements'] ?? 0, 'ContactPage.numberOfElements must be int'),
        );

        return new Page($contacts, $pageInfo);
    }
}


