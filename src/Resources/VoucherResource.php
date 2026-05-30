<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Collections\Page;
use Pirabyte\LaravelLexwareOffice\Dto\Common\DownloadedFile;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\Voucher;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherDocument;
use Pirabyte\LaravelLexwareOffice\Dto\Vouchers\VoucherWrite;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\OptimisticLockingException;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Http\MultipartBody;
use Pirabyte\LaravelLexwareOffice\Http\MultipartPart;
use Pirabyte\LaravelLexwareOffice\Http\QueryParams;
use Pirabyte\LaravelLexwareOffice\Mappers\Vouchers\VoucherDocumentMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Vouchers\VoucherMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Vouchers\VoucherPageMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Vouchers\VoucherWriteMapper;
use Psr\Http\Message\StreamInterface;

class VoucherResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function create(VoucherWrite $voucher): Voucher
    {
        $body = VoucherWriteMapper::toJsonBody($voucher);
        $response = $this->http->postJson('vouchers', $body);

        $decoded = JsonCodec::decode($response->body);
        if (! array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            $id = $decoded['id'] ?? null;
            if (is_string($id) && $id !== '') {
                return $this->get($id);
            }
        }

        return VoucherMapper::fromJson($response->body);
    }

    public function get(string $id): Voucher
    {
        $response = $this->http->get("vouchers/{$id}");

        return VoucherMapper::fromJson($response->body);
    }

    public function update(string $id, VoucherWrite $voucher): Voucher
    {
        if ($voucher->version === null) {
            throw new \InvalidArgumentException('VoucherWrite.version is required for updates (optimistic locking)');
        }

        $body = VoucherWriteMapper::toJsonBody($voucher);
        try {
            $response = $this->http->putJson("vouchers/{$id}", $body);
        } catch (LexwareOfficeApiException $e) {
            if ($e->isConflictError()) {
                throw new OptimisticLockingException(
                    'Voucher update failed due to version conflict',
                    $id,
                    $voucher->version,
                    self::extractVersionFromErrorResponse($e),
                    $e
                );
            }

            throw $e;
        }

        $decoded = JsonCodec::decode($response->body);
        if (! array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            $returnedId = $decoded['id'] ?? null;
            if (is_string($returnedId) && $returnedId !== '') {
                return $this->get($returnedId);
            }
        }

        return VoucherMapper::fromJson($response->body);
    }

    private static function extractVersionFromErrorResponse(LexwareOfficeApiException $e): ?int
    {
        $responseData = json_decode($e->getError()->rawBody, true);
        if (! is_array($responseData)) {
            return null;
        }

        return $responseData['currentVersion']
            ?? $responseData['version']
            ?? $responseData['lockVersion']
            ?? null;
    }

    /**
     * @return Page<Voucher>
     */
    public function filter(string $voucherNumber, int $page = 0, int $size = 25): Page
    {
        $query = QueryParams::empty()
            ->with('voucherNumber', $voucherNumber)
            ->with('page', $page)
            ->with('size', min($size, 100));

        $response = $this->http->get('vouchers', $query);

        return VoucherPageMapper::fromJson($response->body);
    }

    /**
     * @return Page<Voucher>
     */
    public function all(int $page = 0, int $size = 25): Page
    {
        $query = QueryParams::empty()
            ->with('page', $page)
            ->with('size', min($size, 100));

        $response = $this->http->get('vouchers', $query);

        return VoucherPageMapper::fromJson($response->body);
    }

    public function document(string $id): VoucherDocument
    {
        $response = $this->http->postJson("vouchers/{$id}/document", new JsonBody('{}'));

        return VoucherDocumentMapper::fromJson($response->body);
    }

    public function downloadDocument(string $voucherId, string $fileId): DownloadedFile
    {
        $response = $this->http->get("vouchers/{$voucherId}/files/{$fileId}");

        return new DownloadedFile(
            bytes: $response->body,
            contentType: $response->headerLine('Content-Type'),
        );
    }

    public function attachFile(string $id, StreamInterface $stream, string $filename = 'voucher.pdf', string $type = 'voucher'): VoucherDocument
    {
        $multipart = MultipartBody::empty()
            ->withPart(new MultipartPart('file', $stream, $filename))
            ->withPart(new MultipartPart('type', $type));

        $response = $this->http->postMultipart("vouchers/{$id}/files", $multipart);

        return VoucherDocumentMapper::fromJson($response->body);
    }
}
