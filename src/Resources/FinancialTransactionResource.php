<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Models\NewFinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Models\VoucherAssignment;

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
     * @param  array<NewFinancialTransaction>  $transactions  Up to 25 transactions
     * @return array<FinancialTransaction>
     *
     * @throws LexwareOfficeApiException
     */
    public function create(array $transactions): array
    {
        if (count($transactions) > 25) {
            throw new \OutOfRangeException('only 25 transactions allowed per request');
        }

        foreach ($transactions as $transaction) {
            // Erforderliche Felder validieren
            if (! isset($transaction['financialTransactionId']) || ! isset($transaction['valueDate']) ||
                ! isset($transaction['bookingDate']) || ! isset($transaction['purpose']) ||
                ! isset($transaction['amount']) || ! isset($transaction['financialAccountId'])) {
                throw new \InvalidArgumentException('Fehlende erforderliche Felder für FinancialTransaction');
            }
        }

        $response = $this->client->post('finance/transactions', $transactions);

        return $this->processTransactionsResponse($response);
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
     * @return FinancialTransaction Die aktualisierte Finanztransaktion
     *
     * @throws LexwareOfficeApiException
     */
    public function update(string $id, FinancialTransaction $transaction): FinancialTransaction
    {
        $data = $transaction->jsonSerialize();
        $response = $this->client->put("finance/transactions/{$id}", $data);

        return FinancialTransaction::fromArray($response);
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
     * Ruft die neuesten Finanztransaktionen ab
     *
     * @param  array  $filters  Filtermöglichkeiten
     * @return array Liste der Finanztransaktionen
     *
     * @throws LexwareOfficeApiException
     */
    public function latest(array $filters = []): array
    {
        $validFilters = [
            'financialAccountId', 'page', 'size',
        ];

        // HTTP-Query vorbereiten
        $query = [];

        // Nur gültige und nicht-leere Filter einbeziehen
        foreach ($filters as $key => $value) {
            // Leere oder ungültige Filter überspringen
            if (! in_array($key, $validFilters) || $value === null || $value === '') {
                continue;
            }

            // Multiple Werte für den gleichen Filter unterstützen
            if (isset($query[$key])) {
                // Wenn bereits ein Array, füge neuen Wert hinzu
                if (is_array($query[$key])) {
                    $query[$key][] = $value;
                } else {
                    // Sonst konvertiere zu Array mit beiden Werten
                    $query[$key] = [$query[$key], $value];
                }
            } else {
                // Bei einem neuen Filter-Key setzen wir den Wert einfach
                $query[$key] = $value;
            }
        }

        $response = $this->client->get('finance/transactions/latest', $query);

        return $this->processTransactionsResponse($response);
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
