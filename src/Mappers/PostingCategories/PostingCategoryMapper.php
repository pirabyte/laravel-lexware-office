<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\PostingCategories;

use Pirabyte\LaravelLexwareOffice\Collections\PostingCategories\PostingCategoryCollection;
use Pirabyte\LaravelLexwareOffice\Dto\PostingCategories\PostingCategory;
use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class PostingCategoryMapper implements ApiMapper
{
    public static function collectionFromJson(string $rawJson): PostingCategoryCollection
    {
        $data = JsonCodec::decode($rawJson);

        if (! array_is_list($data)) {
            throw new DecodeException('Expected JSON list for PostingCategories', $rawJson);
        }

        $collection = PostingCategoryCollection::empty();
        foreach ($data as $row) {
            $row = Assert::array($row, 'PostingCategory entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('PostingCategory entry must be an object', $rawJson);
            }

            /** @var array<string, mixed> $row */
            try {
                $type = PostingCategoryType::from(Assert::string($row['type'] ?? null, 'PostingCategory.type missing'));
            } catch (\ValueError $e) {
                throw new DecodeException('Invalid PostingCategory.type', $rawJson, $e);
            }

            $collection = $collection->with(new PostingCategory(
                id: Assert::string($row['id'] ?? null, 'PostingCategory.id missing'),
                name: Assert::string($row['name'] ?? null, 'PostingCategory.name missing'),
                type: $type,
                contactRequired: Assert::bool($row['contactRequired'] ?? null, 'PostingCategory.contactRequired missing'),
                splitAllowed: Assert::bool($row['splitAllowed'] ?? null, 'PostingCategory.splitAllowed missing'),
                groupName: Assert::nullableString($row['groupName'] ?? null, 'PostingCategory.groupName must be string|null'),
            ));
        }

        return $collection;
    }
}


