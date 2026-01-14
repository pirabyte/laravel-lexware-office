<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Vouchers;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherFileCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Vouchers\VoucherItemCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\Voucher;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherFile;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class VoucherMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): Voucher
    {
        $data = JsonCodec::decode($rawJson);

        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for Voucher', $rawJson);
        }

        /** @var array<string, mixed> $data */
        return self::fromArray($data, $rawJson);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $rawJson): Voucher
    {
        $voucherItems = VoucherItemCollection::empty();
        $itemsData = Assert::array($data['voucherItems'] ?? [], 'Voucher.voucherItems must be a list');
        if (! array_is_list($itemsData)) {
            throw new DecodeException('Voucher.voucherItems must be a list', $rawJson);
        }

        foreach ($itemsData as $row) {
            $row = Assert::array($row, 'VoucherItem must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('VoucherItem must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */
            $voucherItems = $voucherItems->with(new VoucherItem(
                amount: Assert::float($row['amount'] ?? null, 'VoucherItem.amount missing'),
                taxAmount: Assert::float($row['taxAmount'] ?? null, 'VoucherItem.taxAmount missing'),
                taxRatePercent: Assert::float($row['taxRatePercent'] ?? null, 'VoucherItem.taxRatePercent missing'),
                categoryId: Assert::string($row['categoryId'] ?? null, 'VoucherItem.categoryId missing'),
            ));
        }

        $files = VoucherFileCollection::empty();
        $filesData = Assert::array($data['files'] ?? [], 'Voucher.files must be a list');
        if (! array_is_list($filesData)) {
            throw new DecodeException('Voucher.files must be a list', $rawJson);
        }

        foreach ($filesData as $row) {
            $row = Assert::array($row, 'VoucherFile must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('VoucherFile must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */

            $fileId = Assert::nullableString($row['fileId'] ?? null, 'VoucherFile.fileId must be string|null')
                ?? Assert::nullableString($row['id'] ?? null, 'VoucherFile.id must be string|null');

            if ($fileId === null || $fileId === '') {
                throw new DecodeException('VoucherFile is missing an id', $rawJson);
            }

            $files = $files->with(new VoucherFile(
                id: $fileId,
                fileName: Assert::nullableString($row['fileName'] ?? null, 'VoucherFile.fileName must be string|null'),
                mimeType: Assert::nullableString($row['mimeType'] ?? null, 'VoucherFile.mimeType must be string|null'),
            ));
        }

        return new Voucher(
            id: Assert::string($data['id'] ?? null, 'Voucher.id missing'),
            organizationId: Assert::string($data['organizationId'] ?? null, 'Voucher.organizationId missing'),
            type: Assert::string($data['type'] ?? null, 'Voucher.type missing'),
            voucherStatus: Assert::nullableString($data['voucherStatus'] ?? null, 'Voucher.voucherStatus must be string|null'),
            voucherNumber: Assert::nullableString($data['voucherNumber'] ?? null, 'Voucher.voucherNumber must be string|null'),
            voucherDate: self::date($data['voucherDate'] ?? null, 'Voucher.voucherDate missing', $rawJson),
            shippingDate: self::nullableDate($data['shippingDate'] ?? null, 'Voucher.shippingDate must be string|null', $rawJson),
            dueDate: self::nullableDate($data['dueDate'] ?? null, 'Voucher.dueDate must be string|null', $rawJson),
            totalGrossAmount: Assert::float($data['totalGrossAmount'] ?? null, 'Voucher.totalGrossAmount missing'),
            totalTaxAmount: Assert::nullableFloat($data['totalTaxAmount'] ?? null, 'Voucher.totalTaxAmount must be float|null'),
            taxType: Assert::string($data['taxType'] ?? null, 'Voucher.taxType missing'),
            useCollectiveContact: Assert::bool($data['useCollectiveContact'] ?? false, 'Voucher.useCollectiveContact must be bool'),
            remark: Assert::nullableString($data['remark'] ?? null, 'Voucher.remark must be string|null'),
            voucherItems: $voucherItems,
            files: $files,
            createdDate: self::nullableDate($data['createdDate'] ?? null, 'Voucher.createdDate must be string|null', $rawJson),
            updatedDate: self::nullableDate($data['updatedDate'] ?? null, 'Voucher.updatedDate must be string|null', $rawJson),
            version: Assert::int($data['version'] ?? 0, 'Voucher.version must be int'),
        );
    }

    private static function date(mixed $value, string $message, string $rawJson): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable(Assert::string($value, $message));
        } catch (\Throwable $e) {
            throw new DecodeException($message, $rawJson, $e);
        }
    }

    private static function nullableDate(mixed $value, string $message, string $rawJson): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return self::date($value, $message, $rawJson);
    }
}


