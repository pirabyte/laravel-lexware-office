<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Vouchers;

use Pirabyte\LaravelLexwareOffice\Collections\Page;
use Pirabyte\LaravelLexwareOffice\Collections\PageInfo;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherCollection;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class VoucherPageMapper implements ApiMapper
{
    /**
     * @return Page<\Pirabyte\LaravelLexwareOffice\Dto\Vouchers\Voucher>
     */
    public static function fromJson(string $rawJson): Page
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for paginated vouchers', $rawJson);
        }

        /** @var array<string, mixed> $data */
        $content = Assert::array($data['content'] ?? [], 'VoucherPage.content must be a list');
        if (! array_is_list($content)) {
            throw new DecodeException('VoucherPage.content must be a list', $rawJson);
        }

        $vouchers = VoucherCollection::empty();
        foreach ($content as $row) {
            $row = Assert::array($row, 'VoucherPage.content item must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('VoucherPage.content item must be an object', $rawJson);
            }

            /** @var array<string, mixed> $row */
            $vouchers = $vouchers->with(VoucherMapper::fromArray($row, $rawJson));
        }

        $pageInfo = new PageInfo(
            page: Assert::int($data['number'] ?? 0, 'VoucherPage.number must be int'),
            size: Assert::int($data['size'] ?? 0, 'VoucherPage.size must be int'),
            first: Assert::bool($data['first'] ?? false, 'VoucherPage.first must be bool'),
            last: Assert::bool($data['last'] ?? false, 'VoucherPage.last must be bool'),
            totalPages: Assert::int($data['totalPages'] ?? 0, 'VoucherPage.totalPages must be int'),
            totalElements: Assert::int($data['totalElements'] ?? 0, 'VoucherPage.totalElements must be int'),
            numberOfElements: Assert::int($data['numberOfElements'] ?? 0, 'VoucherPage.numberOfElements must be int'),
        );

        return new Page($vouchers, $pageInfo);
    }
}


