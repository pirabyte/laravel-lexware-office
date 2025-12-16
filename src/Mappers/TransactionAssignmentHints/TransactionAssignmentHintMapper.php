<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\TransactionAssignmentHints;

use Pirabyte\LaravelLexwareOffice\Dto\TransactionAssignmentHints\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class TransactionAssignmentHintMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): TransactionAssignmentHint
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for TransactionAssignmentHint', $rawJson);
        }

        /** @var array<string, mixed> $data */
        return new TransactionAssignmentHint(
            voucherId: Assert::string($data['voucherId'] ?? null, 'TransactionAssignmentHint.voucherId missing'),
            externalReference: Assert::string($data['externalReference'] ?? null, 'TransactionAssignmentHint.externalReference missing'),
        );
    }
}


