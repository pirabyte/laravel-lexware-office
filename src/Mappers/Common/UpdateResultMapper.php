<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Common;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Common\UpdateResult;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class UpdateResultMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): UpdateResult
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for UpdateResult', $rawJson);
        }

        /** @var array<string, mixed> $data */
        try {
            $createdDate = new DateTimeImmutable(Assert::string($data['createdDate'] ?? null, 'UpdateResult.createdDate missing'));
            $updatedDate = new DateTimeImmutable(Assert::string($data['updatedDate'] ?? null, 'UpdateResult.updatedDate missing'));
        } catch (\Throwable $e) {
            throw new DecodeException('Invalid UpdateResult datetime field', $rawJson, $e);
        }

        return new UpdateResult(
            id: Assert::string($data['id'] ?? null, 'UpdateResult.id missing'),
            resourceUri: Assert::string($data['resourceUri'] ?? null, 'UpdateResult.resourceUri missing'),
            createdDate: $createdDate,
            updatedDate: $updatedDate,
            version: Assert::int($data['version'] ?? null, 'UpdateResult.version missing'),
        );
    }
}


