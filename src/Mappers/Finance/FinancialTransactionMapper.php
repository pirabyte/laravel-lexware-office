<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Collections\Finance\FinancialTransactionCollection;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransaction;
use Pirabyte\LaravelLexwareOffice\Enums\TransactionState;
use Pirabyte\LaravelLexwareOffice\Exceptions\DecodeException;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final class FinancialTransactionMapper implements ApiMapper
{
    public static function fromJson(string $rawJson): FinancialTransaction
    {
        $data = JsonCodec::decode($rawJson);
        if (array_is_list($data)) {
            throw new DecodeException('Expected JSON object for FinancialTransaction', $rawJson);
        }

        /** @var array<string, mixed> $data */
        return self::fromArray($data, $rawJson);
    }

    public static function collectionFromJson(string $rawJson): FinancialTransactionCollection
    {
        $data = JsonCodec::decode($rawJson);
        if (! array_is_list($data)) {
            throw new DecodeException('Expected JSON list for FinancialTransactions', $rawJson);
        }

        $collection = FinancialTransactionCollection::empty();
        foreach ($data as $row) {
            $row = Assert::array($row, 'FinancialTransaction entry must be an object');
            if (array_is_list($row)) {
                throw new DecodeException('FinancialTransaction entry must be an object', $rawJson);
            }
            /** @var array<string, mixed> $row */
            $collection = $collection->with(self::fromArray($row, $rawJson));
        }

        return $collection;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $rawJson): FinancialTransaction
    {
        $state = null;
        $stateRaw = $data['state'] ?? null;
        if ($stateRaw !== null) {
            try {
                $state = TransactionState::from(Assert::string($stateRaw, 'FinancialTransaction.state must be string|null'));
            } catch (\ValueError $e) {
                throw new DecodeException('Invalid FinancialTransaction.state', $rawJson, $e);
            }
        }

        return new FinancialTransaction(
            financialTransactionId: Assert::string($data['financialTransactionId'] ?? null, 'FinancialTransaction.financialTransactionId missing'),
            valueDate: self::date($data['valueDate'] ?? null, 'FinancialTransaction.valueDate missing', $rawJson),
            bookingDate: self::nullableDate($data['bookingDate'] ?? null, 'FinancialTransaction.bookingDate must be string|null', $rawJson),
            transactionDate: self::date($data['transactionDate'] ?? ($data['transactiondate'] ?? null), 'FinancialTransaction.transactionDate missing', $rawJson),
            purpose: Assert::string($data['purpose'] ?? null, 'FinancialTransaction.purpose missing'),
            amount: Assert::float($data['amount'] ?? null, 'FinancialTransaction.amount missing'),
            openAmount: Assert::nullableFloat($data['openAmount'] ?? null, 'FinancialTransaction.openAmount must be float|null'),
            amountAsString: Assert::nullableString($data['amountAsString'] ?? null, 'FinancialTransaction.amountAsString must be string|null'),
            openAmountAsString: Assert::nullableString($data['openAmountAsString'] ?? null, 'FinancialTransaction.openAmountAsString must be string|null'),
            additionalInfo: Assert::nullableString($data['additionalInfo'] ?? null, 'FinancialTransaction.additionalInfo must be string|null'),
            state: $state,
            recipientOrSenderName: Assert::nullableString($data['recipientOrSenderName'] ?? null, 'FinancialTransaction.recipientOrSenderName must be string|null'),
            recipientOrSenderEmail: Assert::nullableString($data['recipientOrSenderEmail'] ?? null, 'FinancialTransaction.recipientOrSenderEmail must be string|null'),
            recipientOrSenderIban: Assert::nullableString($data['recipientOrSenderIban'] ?? null, 'FinancialTransaction.recipientOrSenderIban must be string|null'),
            recipientOrSenderBic: Assert::nullableString($data['recipientOrSenderBic'] ?? null, 'FinancialTransaction.recipientOrSenderBic must be string|null'),
            financialAccountId: Assert::string($data['financialAccountId'] ?? null, 'FinancialTransaction.financialAccountId missing'),
            externalReference: Assert::nullableString($data['externalReference'] ?? null, 'FinancialTransaction.externalReference must be string|null'),
            endToEndId: Assert::nullableString($data['endToEndId'] ?? null, 'FinancialTransaction.endToEndId must be string|null'),
            lockVersion: Assert::int($data['lockVersion'] ?? 0, 'FinancialTransaction.lockVersion must be int'),
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


