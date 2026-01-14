<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\TransactionAssignmentHints;

use Pirabyte\LaravelLexwareOffice\Dto\TransactionAssignmentHints\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class TransactionAssignmentHintWriteMapper implements ApiMapper
{
    public static function toJsonBody(TransactionAssignmentHint $hint): JsonBody
    {
        return new JsonBody(JsonCodec::encode([
            'voucherId' => $hint->voucherId,
            'externalReference' => $hint->externalReference,
        ]));
    }
}


