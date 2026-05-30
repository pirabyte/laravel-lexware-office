<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Vouchers;

use DateTimeInterface;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherItem;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherWrite;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class VoucherWriteMapper implements ApiMapper
{
    public static function toJsonBody(VoucherWrite $voucher): JsonBody
    {
        $payload = [
            'type' => $voucher->type,
            'voucherDate' => self::formatDateTime($voucher->voucherDate),
            'totalGrossAmount' => $voucher->totalGrossAmount,
            'taxType' => $voucher->taxType,
            'useCollectiveContact' => $voucher->useCollectiveContact,
            'voucherItems' => self::voucherItemsPayload($voucher->voucherItems),
        ];

        if ($voucher->voucherNumber !== null) {
            $payload['voucherNumber'] = $voucher->voucherNumber;
        }
        if ($voucher->voucherStatus !== null) {
            $payload['voucherStatus'] = $voucher->voucherStatus;
        }
        if ($voucher->shippingDate !== null) {
            $payload['shippingDate'] = self::formatDateTime($voucher->shippingDate);
        }
        if ($voucher->dueDate !== null) {
            $payload['dueDate'] = self::formatDateTime($voucher->dueDate);
        }
        if ($voucher->totalTaxAmount !== null) {
            $payload['totalTaxAmount'] = $voucher->totalTaxAmount;
        }
        if ($voucher->remark !== null) {
            $payload['remark'] = $voucher->remark;
        }
        if ($voucher->version !== null) {
            $payload['version'] = $voucher->version;
        }
        if ($voucher->contactId !== null) {
            $payload['contactId'] = $voucher->contactId;
        }

        return new JsonBody(JsonCodec::encode($payload));
    }

    private static function formatDateTime(DateTimeInterface $dateTime): string
    {
        // ISO 8601 with timezone offset, no milliseconds (Lexware accepts this format).
        return $dateTime->format('Y-m-d\TH:i:sP');
    }

    /**
     * @param  iterable<VoucherItem>  $items
     * @return list<array<string, mixed>>
     */
    private static function voucherItemsPayload(iterable $items): array
    {
        $payload = [];
        foreach ($items as $item) {
            $payload[] = [
                'amount' => $item->amount,
                'taxAmount' => $item->taxAmount,
                'taxRatePercent' => $item->taxRatePercent,
                'categoryId' => $item->categoryId,
            ];
        }

        return $payload;
    }
}


