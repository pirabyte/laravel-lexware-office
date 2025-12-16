<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountCreate;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class FinancialAccountCreateMapper implements ApiMapper
{
    public static function toJsonBody(FinancialAccountCreate $account): JsonBody
    {
        $payload = [
            'financialAccountId' => $account->financialAccountId,
            'type' => $account->type->value,
            'accountSystem' => $account->accountSystem->value,
            'name' => $account->name,
        ];

        if ($account->bankName !== null) {
            $payload['bankName'] = $account->bankName;
        }
        if ($account->externalReference !== null) {
            $payload['externalReference'] = $account->externalReference;
        }

        return new JsonBody(JsonCodec::encode($payload));
    }
}


