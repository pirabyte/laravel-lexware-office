<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\CreditNote;
use Pirabyte\LaravelLexwareOffice\Requests\CreditNoteCreateRequest;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;
use Pirabyte\LaravelLexwareOffice\Responses\UpdateResponse;
use Pirabyte\LaravelLexwareOffice\Traits\DownloadsElectronicDocuments;

class CreditNoteResource
{
    use DownloadsElectronicDocuments;

    public function __construct(protected LexwareOffice $client) {}

    public function create(CreditNoteCreateRequest $request, bool $finalize = false, ?string $precedingSalesVoucherId = null): UpdateResponse
    {
        $query = ['finalize' => $finalize ? 'true' : 'false'];
        if ($precedingSalesVoucherId !== null) {
            $query['precedingSalesVoucherId'] = $precedingSalesVoucherId;
        }

        return UpdateResponse::fromArray($this->client->post('credit-notes', $request->jsonSerialize(), $query));
    }

    public function get(string $id): CreditNote
    {
        return CreditNote::fromArray($this->client->get('credit-notes/'.rawurlencode($id)));
    }

    public function file(string $id, FileFormat $format = FileFormat::DEFAULT): FileDownload
    {
        return $this->downloadDocumentFile($id, 'credit-notes/'.rawurlencode($id).'/file', $format);
    }
}
