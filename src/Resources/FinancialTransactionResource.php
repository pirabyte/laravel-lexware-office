<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Models\VoucherAssignment;
use Pirabyte\LaravelLexwareOffice\Responses\UpdateResponse;

class FinancialTransactionResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Creates transactions for the account referenced by the financialAccountId.
     *
     * @param  array<FinancialTransaction|array>  $transactions  Up to 25 transactions
     * @return array<FinancialTransaction>
     *
     * @throws LexwareOfficeApiException
     */
    public function create(array $transactions): array
    {
        if (count($transactions) > 25) {
            throw new \OutOfRangeException('only 25 transactions allowed per request');
        }

        // Convert transactions to array format
        $requestData = [];
        foreach ($transactions as $transaction) {
            if ($transaction instanceof FinancialTransaction) {
                $requestData[] = $transaction->jsonSerialize();
            } else {
                $requestData[] = $transaction;
            }
        }

        $response = $this->client->post('finance/transactions', $requestData);

        // Response is directly an array of transactions
        $transactions = [];
        foreach ($response as $transactionData) {
            $transactions[] = FinancialTransaction::fromArray($transactionData);
        }

        return $transactions;
    }

    /**
     * Ruft eine Finanztransaktion anhand der ID ab
     *
     * @param  string  $id  Die ID der Finanztransaktion
     * @return FinancialTransaction Die abgerufene Finanztransaktion
     *
     * @throws LexwareOfficeApiException
     */
    public function get(string $id): FinancialTransaction
    {
        $response = $this->client->get("finance/transactions/{$id}");

        return FinancialTransaction::fromArray($response);
    }

    /**
     * Aktualisiert eine bestehende Finanztransaktion
     *
     * @param  string  $id  Die ID der Finanztransaktion
     * @param  FinancialTransaction  $transaction  Die aktualisierte Finanztransaktion
     * @return UpdateResponse Die Update-Response mit der neuen Version
     *
     * @throws LexwareOfficeApiException
     * @throws \InvalidArgumentException
     */
    public function update(string $id, FinancialTransaction $transaction): UpdateResponse
    {
        if ($transaction->getLockVersion() === null) {
            throw new \InvalidArgumentException('lockVersion is required for updates (optimistic locking)');
        }

        $data = $transaction->jsonSerialize();
        
        // Remove read-only fields that should not be sent in updates
        unset($data['financialTransactionId']);
        unset($data['transactionDate']);
        unset($data['openAmount']);
        unset($data['amountAsString']);
        unset($data['openAmountAsString']);
        unset($data['state']);
        unset($data['createdDate']);
        unset($data['lastModifiedDate']);
        unset($data['endToEndId']);
        unset($data['bookingText']);
        unset($data['virtualAccountId']);
        unset($data['ignore']);
        unset($data['ignoreReason']);
        
        $response = $this->client->put("finance/transactions/{$id}", $data);

        return UpdateResponse::fromArray($response);
    }

    /**
     * Löscht eine Finanztransaktion anhand der ID
     *
     * @param  string  $id  Die ID der Finanztransaktion
     * @return bool true, wenn das Löschen erfolgreich war
     *
     * @throws LexwareOfficeApiException
     */
    public function delete(string $id): bool
    {
        $this->client->delete("finance/transactions/{$id}");

        return true;
    }

    /**
     * Ruft die neueste Finanztransaktion für ein Konto ab
     *
     * @param  string  $financialAccountId  Die ID des Finanzkontos
     * @return FinancialTransaction|null Die neueste Transaktion oder null wenn keine vorhanden
     *
     * @throws LexwareOfficeApiException
     */
    public function latest(string $financialAccountId): ?FinancialTransaction
    {
        $query = [
            'financialAccountId' => $financialAccountId,
        ];

        $response = $this->client->get('finance/transactions/latest-transaction', $query);
        
        // Return null if no transaction found
        if (empty($response)) {
            return null;
        }
        
        return FinancialTransaction::fromArray($response);
    }

    /**
     * Ruft die Belegzuweisungen für eine Finanztransaktion ab
     *
     * @param  string  $id  Die ID der Finanztransaktion
     * @return array Liste der Belegzuweisungen
     *
     * @throws LexwareOfficeApiException
     */
    public function getVoucherAssignments(string $id): array
    {
        $response = $this->client->get("finance/transactions/{$id}/assignments");

        return $this->processVoucherAssignmentsResponse($response);
    }

    /**
     * Verarbeitet die Antwort der Transactions-API und erstellt daraus ein strukturiertes Array
     *
     * @param  array  $response  API-Antwort
     * @return array Ein Array mit FinancialTransaction-Objekten
     */
    protected function processTransactionsResponse(array $response): array
    {
        $transactions = [];

        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $transactionData) {
                $transactions[] = FinancialTransaction::fromArray($transactionData);
            }
        }

        return $transactions;
    }

    /**
     * Verarbeitet die Antwort der Assignments-API und erstellt daraus ein strukturiertes Array
     *
     * @param  array  $response  API-Antwort
     * @return array Ein Array mit VoucherAssignment-Objekten
     */
    protected function processVoucherAssignmentsResponse(array $response): array
    {
        $assignments = [];

        if (isset($response['assignments']) && is_array($response['assignments'])) {
            foreach ($response['assignments'] as $assignmentData) {
                $assignments[] = VoucherAssignment::fromArray($assignmentData);
            }
        }

        return $assignments;
    }
}
