<?php

namespace Pirabyte\LaravelLexwareOffice\Responses;

use GuzzleHttp\Psr7\Header;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FileDownload
{
    public function __construct(
        public readonly StreamInterface $stream,
        public readonly ?string $mimeType,
        public readonly ?string $filename,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        $disposition = Header::parse($response->getHeader('Content-Disposition'))[0] ?? [];
        $filename = $disposition['filename'] ?? null;

        if (isset($disposition['filename*']) && preg_match("/^UTF-8'[^']*'(.*)$/i", $disposition['filename*'], $matches)) {
            $filename = rawurldecode($matches[1]);
        }

        // This is a suggested name, never a server-provided filesystem path.
        if ($filename !== null) {
            $filename = basename(str_replace('\\', '/', $filename));
            $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
            if ($filename === '' || $filename === '.' || $filename === '..') {
                $filename = null;
            }
        }

        $mimeType = trim(explode(';', $response->getHeaderLine('Content-Type'), 2)[0]);

        return new self($response->getBody(), $mimeType !== '' ? $mimeType : null, $filename);
    }
}
