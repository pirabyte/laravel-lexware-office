<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\FinancialAccount;

class FinancialAccountResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Ruft ein Finanzkonto anhand der ID ab
     *
     * @param  string  $id  Die ID des Finanzkontos
     * @return FinancialAccount Das abgerufene Finanzkonto
     *
     * @throws LexwareOfficeApiException
     */
    public function get(string $id): FinancialAccount
    {
        $response = $this->client->get("financial-accounts/{$id}");

        return FinancialAccount::fromArray($response);
    }

    /**
     * Finanzkonten nach verschiedenen Kriterien filtern
     *
     * @param  array  $filters  Filtermöglichkeiten:
     *                          - type: string - Filtert nach Kontentyp (GIRO, SAVINGS, etc.)
     *                          - deactivated: bool - Filtert nach deaktivierten Konten
     *                          - page: int - Seitennummer (beginnend bei 0)
     *                          - size: int - Anzahl der Ergebnisse pro Seite
     * @return array Liste der gefilterten Finanzkonten
     *
     * @throws LexwareOfficeApiException
     */
    public function filter(array $filters = []): array
    {
        $validFilters = [
            'type', 'deactivated', 'page', 'size',
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

        // API-Anfrage senden
        $response = $this->client->get('financial-accounts', $query);

        return $this->processFinancialAccountsResponse($response);
    }

    /**
     * Verarbeitet die Antwort der Financial-Accounts-API und erstellt daraus ein strukturiertes Array
     *
     * @param  array  $response  API-Antwort
     * @return array Ein Array mit FinancialAccount-Objekten
     */
    protected function processFinancialAccountsResponse(array $response): array
    {
        $accounts = [];

        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $accountData) {
                $accounts[] = FinancialAccount::fromArray($accountData);
            }
        }

        return $accounts;
    }

    /**
     * Löscht ein Finanzkonto anhand der ID
     *
     * Hinweis: Ein Konto kann nur gelöscht werden, wenn keine Transaktionen an Belege in lexoffice
     * zugewiesen sind. Andernfalls wird ein 406-Statuscode zurückgegeben.
     *
     * @param  string  $id  Die ID des zu löschenden Finanzkontos
     * @return bool true, wenn das Löschen erfolgreich war (Statuscode 204)
     *
     * @throws LexwareOfficeApiException Bei Fehlerstatuscode 406, wenn das Konto nicht gelöscht werden kann
     */
    public function delete(string $id): bool
    {
        try {
            $this->client->delete("financial-accounts/{$id}");

            return true;
        } catch (LexwareOfficeApiException $e) {
            // 406 bedeutet, dass das Konto Transaktionen hat, die Belegen zugewiesen sind
            if ($e->getCode() === 406) {
                throw new LexwareOfficeApiException(
                    'Das Finanzkonto kann nicht gelöscht werden, da es Transaktionen enthält, die Belegen zugewiesen sind. '.
                    'Der Benutzer muss zuerst manuell in lexoffice die Zuweisungen aufheben.',
                    406,
                    $e
                );
            }

            // Andere Fehler weiterleiten
            throw $e;
        }
    }
}
