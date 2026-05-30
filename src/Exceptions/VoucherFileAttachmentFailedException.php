<?php

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use RuntimeException;

/**
 * Raised when a voucher was created but the immediate file upload failed.
 */
final class VoucherFileAttachmentFailedException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $voucher  Metadata for the already-created voucher.
     * @param  \Throwable  $previous  Original upload failure.
     */
    public function __construct(
        private readonly array $voucher,
        \Throwable $previous,
    ) {
        parent::__construct(
            'Voucher was created, but attaching the file failed.',
            0,
            $previous,
        );
    }

    /**
     * Get the voucher that was created before the file upload failed.
     *
     * @return array<string, mixed>
     */
    public function getVoucher(): array
    {
        return $this->voucher;
    }
}
