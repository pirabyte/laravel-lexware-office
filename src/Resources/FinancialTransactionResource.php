<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialTransactionCollection;
use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialTransactionCreateBatch;
use Pirabyte\LaravelLexwareOffice\Collections\Finance\VoucherAssignmentCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Common\UpdateResult;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionUpdate;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Http\QueryParams;
use Pirabyte\LaravelLexwareOffice\Mappers\Common\UpdateResultMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialTransactionCreateBatchMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialTransactionMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialTransactionUpdateMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\VoucherAssignmentMapper;

class FinancialTransactionResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function create(FinancialTransactionCreateBatch $transactions): FinancialTransactionCollection
    {
        $body = FinancialTransactionCreateBatchMapper::toJsonBody($transactions);
        $response = $this->http->postJson('finance/transactions', $body);

        return FinancialTransactionMapper::collectionFromJson($response->body);
    }

    public function get(string $id): FinancialTransaction
    {
        $response = $this->http->get("finance/transactions/{$id}");

        return FinancialTransactionMapper::fromJson($response->body);
    }

    public function update(string $id, FinancialTransactionUpdate $update): UpdateResult
    {
        $body = FinancialTransactionUpdateMapper::toJsonBody($update);
        $response = $this->http->putJson("finance/transactions/{$id}", $body);

        return UpdateResultMapper::fromJson($response->body);
    }

    public function delete(string $id): bool
    {
        $this->http->delete("finance/transactions/{$id}");

        return true;
    }

    public function latest(string $financialAccountId): ?FinancialTransaction
    {
        $query = QueryParams::empty()->with('financialAccountId', $financialAccountId);
        $response = $this->http->get('finance/transactions/latest-transaction', $query);

        $body = trim($response->body);
        if ($body === '' || $body === '[]' || $body === 'null') {
            return null;
        }

        $decoded = JsonCodec::decode($response->body);
        if ($decoded === []) {
            return null;
        }

        return FinancialTransactionMapper::fromJson($response->body);
    }

    public function getVoucherAssignments(string $id): VoucherAssignmentCollection
    {
        $response = $this->http->get("finance/transactions/{$id}/assignments");

        return VoucherAssignmentMapper::collectionFromJson($response->body);
    }
}
