<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\PartnerIntegrations;

use Pirabyte\LaravelLexwareOffice\Dto\Common\KeyValue;
use Pirabyte\LaravelLexwareOffice\Dto\PartnerIntegrations\PartnerIntegration;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class PartnerIntegrationWriteMapper implements ApiMapper
{
    public static function toJsonBody(PartnerIntegration $integration): JsonBody
    {
        $data = [];
        foreach ($integration->data as $pair) {
            /** @var KeyValue $pair */
            $data[$pair->key] = $pair->value;
        }

        $payload = [
            'partnerId' => $integration->partnerId,
            'customerNumber' => $integration->customerNumber,
            'externalId' => $integration->externalId,
            'data' => $data,
        ];

        return new JsonBody(JsonCodec::encode($payload));
    }
}


