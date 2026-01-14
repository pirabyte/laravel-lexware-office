<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialAccountCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccount;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountCreate;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountQuery;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Http\QueryParams;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialAccountCreateMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\Finance\FinancialAccountMapper;

class FinancialAccountResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function create(FinancialAccountCreate $financialAccount): FinancialAccount
    {
        $body = FinancialAccountCreateMapper::toJsonBody($financialAccount);
        $response = $this->http->postJson('finance/financial-accounts', $body);

        $decoded = JsonCodec::decode($response->body);
        if (! array_is_list($decoded)) {
            /** @var array<string, mixed> $decoded */
            $id = $decoded['financialAccountId'] ?? null;
            if (is_string($id) && $id !== '') {
                return $this->get($id);
            }
        }

        return FinancialAccountMapper::fromJson($response->body);
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
        $response = $this->http->get("finance/accounts/{$id}");

        return FinancialAccountMapper::fromJson($response->body);
    }

    public function filter(FinancialAccountQuery $filters = new FinancialAccountQuery()): FinancialAccountCollection
    {
        $query = QueryParams::empty();
        if ($filters->iban !== null && $filters->iban !== '') {
            $query = $query->with('iban', $filters->iban);
        }
        if ($filters->externalReference !== null && $filters->externalReference !== '') {
            $query = $query->with('externalReference', $filters->externalReference);
        }

        $response = $this->http->get('finance/accounts', $query);

        // API returns a list.
        return FinancialAccountMapper::collectionFromJson($response->body);
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
        // Let the HTTP layer surface API errors (including 406) as LexwareOfficeApiException.
        $this->http->delete("financial-accounts/{$id}");

        return true;
    }
}
