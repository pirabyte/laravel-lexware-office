<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use DateTimeInterface;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionCreate;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class FinancialTransactionCreateBatchMapper implements ApiMapper
{
    /**
     * @param  iterable<FinancialTransactionCreate>  $transactions
     */
    public static function toJsonBody(iterable $transactions): JsonBody
    {
        $payload = [];
        foreach ($transactions as $transaction) {
            $row = [
                'valueDate' => self::formatDateTime($transaction->valueDate),
                'bookingDate' => self::formatDateTime($transaction->bookingDate),
                // API expects lowercase key for create requests.
                'transactiondate' => self::formatDateTime($transaction->transactionDate),
                'purpose' => $transaction->purpose,
                'amount' => $transaction->amount,
                'financialAccountId' => $transaction->financialAccountId,
            ];

            if ($transaction->externalReference !== null) {
                $row['externalReference'] = $transaction->externalReference;
            }
            if ($transaction->additionalInfo !== null) {
                $row['additionalInfo'] = $transaction->additionalInfo;
            }
            if ($transaction->recipientOrSenderName !== null) {
                $row['recipientOrSenderName'] = $transaction->recipientOrSenderName;
            }
            if ($transaction->recipientOrSenderEmail !== null) {
                $row['recipientOrSenderEmail'] = $transaction->recipientOrSenderEmail;
            }
            if ($transaction->recipientOrSenderIban !== null) {
                $row['recipientOrSenderIban'] = $transaction->recipientOrSenderIban;
            }
            if ($transaction->recipientOrSenderBic !== null) {
                $row['recipientOrSenderBic'] = $transaction->recipientOrSenderBic;
            }
            if ($transaction->feeAmount !== null) {
                $row['feeAmount'] = $transaction->feeAmount;
            }
            if ($transaction->feeTaxRatePercentage !== null) {
                $row['feeTaxRatePercentage'] = $transaction->feeTaxRatePercentage;
            }
            if ($transaction->feePostingCategoryId !== null) {
                $row['feePostingCategoryId'] = $transaction->feePostingCategoryId;
            }

            $payload[] = $row;
        }

        return new JsonBody(JsonCodec::encode($payload));
    }

    private static function formatDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:sP');
    }
}


