<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use Pirabyte\LaravelLexwareOffice\Collections\Finance\VoucherAssignmentCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\VoucherAssignment;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class VoucherAssignmentMapper implements ApiMapper
{
    public static function collectionFromJson(string $rawJson): VoucherAssignmentCollection
    {
        $decoded = JsonCodec::decode($rawJson);

        if (array_is_list($decoded)) {
            return self::fromList($decoded, $rawJson);
        }

        /** @var array<string, mixed> $decoded */
        $assignments = Assert::array($decoded['assignments'] ?? [], 'VoucherAssignments.assignments must be a list');
        if (! array_is_list($assignments)) {
            throw new DecodeException('VoucherAssignments.assignments must be a list', $rawJson);
        }

        return self::fromList($assignments, $rawJson);
    }

    /**
     * @param  list<mixed>  $list
     */
    private static function fromList(array $list, string $rawJson): VoucherAssignmentCollection
    {
        $collection = VoucherAssignmentCollection::empty();
        foreach ($list as $row) {
            $row = Assert::array($row, 'VoucherAssignment entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('VoucherAssignment entry must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */
            $collection = $collection->with(new VoucherAssignment(
                id: Assert::string($row['id'] ?? null, 'VoucherAssignment.id missing'),
                type: Assert::string($row['type'] ?? null, 'VoucherAssignment.type missing'),
            ));
        }

        return $collection;
    }
}


