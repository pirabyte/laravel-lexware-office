<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Vouchers;

use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherDocument;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class VoucherDocumentMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): VoucherDocument
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for VoucherDocument', $rawJson);
        }

        /** @var array<string, mixed> $data */
        $fileId = $data['fileId'] ?? $data['id'] ?? null;
        if (! is_string($fileId) || $fileId === '') {
            throw new DecodeException('VoucherDocument.fileId missing', $rawJson);
        }

        return new VoucherDocument(
            fileId: $fileId,
            fileName: Assert::nullableString($data['fileName'] ?? null, 'VoucherDocument.fileName must be string|null'),
            mimeType: Assert::nullableString($data['mimeType'] ?? null, 'VoucherDocument.mimeType must be string|null'),
        );
    }
}


