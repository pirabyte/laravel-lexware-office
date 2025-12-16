<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\PartnerIntegrations;

use Pirabyte\LaravelLexwareOffice\Collections\KeyValueCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Common\KeyValue;
use Pirabyte\LaravelLexwareOffice\Dto\PartnerIntegrations\PartnerIntegration;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class PartnerIntegrationMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): PartnerIntegration
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for PartnerIntegration', $rawJson);
        }

        /** @var array<string, mixed> $data */
        $dataField = Assert::array($data['data'] ?? [], 'PartnerIntegration.data must be an object');
        if (array_is_list($dataField)) {
            throw new DecodeException('PartnerIntegration.data must be an object', $rawJson);
        }

        /** @var array<string, mixed> $dataField */
        $pairs = KeyValueCollection::empty();
        foreach ($dataField as $k => $v) {
            if (! is_string($k) || $k === '') {
                continue;
            }
            if (is_string($v)) {
                $value = $v;
            } elseif (is_int($v) || is_float($v) || is_bool($v)) {
                $value = (string) $v;
            } elseif ($v === null) {
                $value = '';
            } else {
                throw new DecodeException('PartnerIntegration.data values must be scalar', $rawJson);
            }

            $pairs = $pairs->with(new KeyValue($k, $value));
        }

        return new PartnerIntegration(
            partnerId: Assert::string($data['partnerId'] ?? null, 'PartnerIntegration.partnerId missing'),
            customerNumber: Assert::string($data['customerNumber'] ?? null, 'PartnerIntegration.customerNumber missing'),
            externalId: Assert::string($data['externalId'] ?? null, 'PartnerIntegration.externalId missing'),
            data: $pairs,
        );
    }
}


