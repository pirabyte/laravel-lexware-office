<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Countries;

use Pirabyte\LaravelLexwareOffice\Collections\Countries\CountryCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Countries\Country;
use Pirabyte\LaravelLexwareOffice\Enums\TaxClassification;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class CountryMapper implements ApiMapper
{
    public static function collectionFromJson(string $rawJson): CountryCollection
    {
        $data = JsonCodec::decode($rawJson);

        if (! array_is_list($data)) {
            throw new DecodeException('Expected JSON list for Countries', $rawJson);
        }

        $collection = CountryCollection::empty();
        foreach ($data as $row) {
            $row = Assert::array($row, 'Country entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('Country entry must be an object', $rawJson);
            }

            /** @var array<string, mixed> $row */
            try {
                $tax = TaxClassification::from(Assert::string($row['taxClassification'] ?? null, 'Country.taxClassification missing'));
            } catch (\ValueError $e) {
                throw new DecodeException('Invalid Country.taxClassification', $rawJson, $e);
            }

            $collection = $collection->with(new Country(
                countryCode: Assert::string($row['countryCode'] ?? null, 'Country.countryCode missing'),
                countryNameDE: Assert::string($row['countryNameDE'] ?? null, 'Country.countryNameDE missing'),
                countryNameEN: Assert::string($row['countryNameEN'] ?? null, 'Country.countryNameEN missing'),
                taxClassification: $tax,
            ));
        }

        return $collection;
    }
}


