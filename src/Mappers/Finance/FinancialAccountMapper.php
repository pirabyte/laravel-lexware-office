<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialAccountCollection;
use Pirabyte\LaravelLexwareOffice\Collections\StringList;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccount;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialAccountState;
use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class FinancialAccountMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): FinancialAccount
    {
        $decoded = JsonCodec::decode($rawJson);

        if (array_is_list($decoded)) {
            // Some endpoints return a single-item list; accept that.
            if ($decoded === [] || ! isset($decoded[0])) {
                throw new DecodeException('Expected FinancialAccount object (or single-item list)', $rawJson);
            }

            $row = Assert::array($decoded[0], 'FinancialAccount entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('FinancialAccount entry must be an object', $rawJson);
            }

            /** @var array<string, mixed> $row */
            return self::fromArray($row, $rawJson);
        }

        /** @var array<string, mixed> $decoded */
        return self::fromArray($decoded, $rawJson);
    }

    public static function collectionFromJson(string $rawJson): FinancialAccountCollection
    {
        $decoded = JsonCodec::decode($rawJson);
        if (! array_is_list($decoded)) {
            throw new DecodeException('Expected JSON list for FinancialAccounts', $rawJson);
        }

        $collection = FinancialAccountCollection::empty();
        foreach ($decoded as $row) {
            $row = Assert::array($row, 'FinancialAccount entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('FinancialAccount entry must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */
            $collection = $collection->with(self::fromArray($row, $rawJson));
        }

        return $collection;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function fromArray(array $data, string $rawJson): FinancialAccount
    {
        try {
            $type = FinancialAccountType::from(Assert::string($data['type'] ?? null, 'FinancialAccount.type missing'));
        } catch (\ValueError $e) {
            throw new DecodeException('Invalid FinancialAccount.type', $rawJson, $e);
        }

        $accountSystemValue = Assert::string($data['accountSystem'] ?? 'UNKNOWN', 'FinancialAccount.accountSystem must be string');
        try {
            $accountSystem = AccountSystem::from($accountSystemValue);
        } catch (\ValueError) {
            $accountSystem = AccountSystem::UNKNOWN;
        }

        $stateData = Assert::array($data['state'] ?? null, 'FinancialAccount.state must be an object');
        if (array_is_list($stateData)) {
            throw new DecodeException('FinancialAccount.state must be an object', $rawJson);
        }

        /** @var array<string, mixed> $stateData */
        $state = new FinancialAccountState(
            organizationId: Assert::string($stateData['organizationId'] ?? null, 'FinancialAccount.state.organizationId missing'),
            status: Assert::string($stateData['status'] ?? null, 'FinancialAccount.state.status missing'),
            errorMessage: Assert::nullableString($stateData['errorMessage'] ?? null, 'FinancialAccount.state.errorMessage must be string|null'),
            errorOnSync: Assert::bool($stateData['errorOnSync'] ?? false, 'FinancialAccount.state.errorOnSync must be bool'),
            syncStartDate: self::nullableDate($stateData['syncStartDate'] ?? null, 'FinancialAccount.state.syncStartDate must be string|null', $rawJson),
            syncEndDate: self::nullableDate($stateData['syncEndDate'] ?? null, 'FinancialAccount.state.syncEndDate must be string|null', $rawJson),
        );

        $availableActions = StringList::fromIterable(Assert::array($data['availableActions'] ?? [], 'FinancialAccount.availableActions must be a list'));

        $virtualAccountId = Assert::nullableString($data['virtualAccountId'] ?? null, 'FinancialAccount.virtualAccountId must be string|null')
            ?? Assert::nullableString($data['virtualAcountId'] ?? null, 'FinancialAccount.virtualAcountId must be string|null');

        return new FinancialAccount(
            organizationId: Assert::string($data['organizationId'] ?? null, 'FinancialAccount.organizationId missing'),
            type: $type,
            name: Assert::string($data['name'] ?? null, 'FinancialAccount.name missing'),
            bankName: Assert::nullableString($data['bankName'] ?? null, 'FinancialAccount.bankName must be string|null'),
            balance: Assert::nullableFloat($data['balance'] ?? null, 'FinancialAccount.balance must be float|null'),
            balanceAccessible: Assert::bool($data['balanceAccessible'] ?? false, 'FinancialAccount.balanceAccessible must be bool'),
            financialAccountId: Assert::string($data['financialAccountId'] ?? null, 'FinancialAccount.financialAccountId missing'),
            state: $state,
            externalReference: Assert::nullableString($data['externalReference'] ?? null, 'FinancialAccount.externalReference must be string|null'),
            initialSyncDate: self::nullableDate($data['initialSyncDate'] ?? null, 'FinancialAccount.initialSyncDate must be string|null', $rawJson),
            lockVersion: Assert::int($data['lockVersion'] ?? 0, 'FinancialAccount.lockVersion must be int'),
            accountSystem: $accountSystem,
            availableActions: $availableActions,
            connected: Assert::bool($data['connected'] ?? false, 'FinancialAccount.connected must be bool'),
            deactivated: Assert::bool($data['deactivated'] ?? false, 'FinancialAccount.deactivated must be bool'),
            synchronizable: Assert::bool($data['synchronizable'] ?? false, 'FinancialAccount.synchronizable must be bool'),
            canIgnoreTransactions: Assert::bool($data['canIgnoreTransactions'] ?? false, 'FinancialAccount.canIgnoreTransactions must be bool'),
            createdDate: self::date($data['createdDate'] ?? null, 'FinancialAccount.createdDate missing', $rawJson),
            lastModifiedDate: self::date($data['lastModifiedDate'] ?? null, 'FinancialAccount.lastModifiedDate missing', $rawJson),
            virtualAccountId: $virtualAccountId,
            brandColor: Assert::nullableString($data['brandColor'] ?? null, 'FinancialAccount.brandColor must be string|null'),
            brandLogoUrlLight: Assert::nullableString($data['brandLogoUrlLight'] ?? null, 'FinancialAccount.brandLogoUrlLight must be string|null'),
            brandLogoUrlDark: Assert::nullableString($data['brandLogoUrlDark'] ?? null, 'FinancialAccount.brandLogoUrlDark must be string|null'),
            brandLogoUrlColor: Assert::nullableString($data['brandLogoUrlColor'] ?? null, 'FinancialAccount.brandLogoUrlColor must be string|null'),
            brandLogoUrlAvatar: Assert::nullableString($data['brandLogoUrlAvatar'] ?? null, 'FinancialAccount.brandLogoUrlAvatar must be string|null'),
            usingProfileType: Assert::nullableString($data['usingProfileType'] ?? null, 'FinancialAccount.usingProfileType must be string|null'),
        );
    }

    private static function date(mixed $value, string $message, string $rawJson): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable(Assert::string($value, $message));
        } catch (\Throwable $e) {
            throw new DecodeException($message, $rawJson, $e);
        }
    }

    private static function nullableDate(mixed $value, string $message, string $rawJson): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return self::date($value, $message, $rawJson);
    }
}


