<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Invoice;
use Pirabyte\LaravelLexwareOffice\Requests\InvoiceCreateRequest;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;
use Pirabyte\LaravelLexwareOffice\Responses\UpdateResponse;
use Pirabyte\LaravelLexwareOffice\Traits\DownloadsElectronicDocuments;

class InvoiceResource
{
    use DownloadsElectronicDocuments;

    public function __construct(protected LexwareOffice $client) {}

    public function create(InvoiceCreateRequest $request, bool $finalize = false): UpdateResponse
    {
        $query = ['finalize' => $finalize ? 'true' : 'false'];

        return UpdateResponse::fromArray($this->client->post('invoices', $request->jsonSerialize(), $query));
    }

    public function get(string $id): Invoice
    {
        return Invoice::fromArray($this->client->get('invoices/'.rawurlencode($id)));
    }

    public function file(string $id, FileFormat $format = FileFormat::DEFAULT): FileDownload
    {
        return $this->downloadDocumentFile($id, 'invoices/'.rawurlencode($id).'/file', $format);
    }
}
