<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;

class FileResource
{
    public function __construct(protected LexwareOffice $client) {}

    public function download(string $id, FileFormat $format = FileFormat::DEFAULT): FileDownload
    {
        return $this->client->download('files/'.rawurlencode($id), $format);
    }
}
