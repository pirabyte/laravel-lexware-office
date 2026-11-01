<?php

namespace Pirabyte\LaravelLexwareOffice\Traits;

use Pirabyte\LaravelLexwareOffice\Enums\ElectronicDocumentProfile;
use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\Enums\VoucherStatus;
use Pirabyte\LaravelLexwareOffice\Exceptions\ElectronicDocumentNotAvailableException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Models\SalesVoucher;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;

trait DownloadsElectronicDocuments
{
    abstract public function get(string $id): SalesVoucher;

    abstract public function file(string $id, FileFormat $format = FileFormat::DEFAULT): FileDownload;

    /**
     * Download the electronic format selected by Lexware.
     *
     * @throws ElectronicDocumentNotAvailableException
     */
    public function electronicDocument(string $id): FileDownload
    {
        $voucher = $this->get($id);
        if ($voucher->voucherStatus === VoucherStatus::DRAFT) {
            throw new LexwareOfficeApiException(
                'Draft vouchers do not have a document. Finalize the voucher in Lexware before downloading its electronic document.',
                LexwareOfficeApiException::STATUS_CONFLICT,
            );
        }

        $profile = $voucher->electronicDocumentProfile;
        $format = match ($profile) {
            ElectronicDocumentProfile::XRECHNUNG => FileFormat::XML,
            ElectronicDocumentProfile::EN16931 => FileFormat::PDF,
            ElectronicDocumentProfile::NONE => throw new ElectronicDocumentNotAvailableException($profile),
        };

        return $this->file($id, $format);
    }

    /**
     * Enrich an XML 404 only when the voucher profile explains the missing file.
     * Preserve the original download error if the diagnostic lookup fails.
     */
    protected function downloadDocumentFile(string $id, string $endpoint, FileFormat $format): FileDownload
    {
        try {
            return $this->client->download($endpoint, $format);
        } catch (LexwareOfficeApiException $e) {
            if ($format !== FileFormat::XML || ! $e->isNotFoundError()) {
                throw $e;
            }

            try {
                $profile = $this->get($id)->electronicDocumentProfile;
            } catch (\Throwable) {
                throw $e;
            }

            if ($profile !== ElectronicDocumentProfile::XRECHNUNG) {
                throw new ElectronicDocumentNotAvailableException($profile, $e);
            }

            throw $e;
        }
    }
}
