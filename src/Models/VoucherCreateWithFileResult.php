<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

/**
 * Result of creating a voucher and attaching a file in one package-level workflow.
 */
final class VoucherCreateWithFileResult
{
    /**
     * @param  array<string, mixed>  $voucher  Metadata returned by Lexware for the created voucher.
     * @param  array<string, mixed>  $file  Metadata returned by Lexware for the uploaded file.
     */
    public function __construct(
        public readonly array $voucher,
        public readonly array $file,
    ) {}
}
