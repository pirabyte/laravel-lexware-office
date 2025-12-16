<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Mappers\Finance;

use DateTimeInterface;
use Pirabyte\LaravelLexwareOffice\Dto\Finance\FinancialTransactionUpdate;
use Pirabyte\LaravelLexwareOffice\Http\JsonBody;
use Pirabyte\LaravelLexwareOffice\Http\JsonCodec;
use Pirabyte\LaravelLexwareOffice\Mappers\ApiMapper;

final class FinancialTransactionUpdateMapper implements ApiMapper
{
    public static function toJsonBody(FinancialTransactionUpdate $update): JsonBody
    {
        $payload = [
            'lockVersion' => $update->lockVersion,
        ];

        if ($update->valueDate !== null) {
            $payload['valueDate'] = self::formatDateTime($update->valueDate);
        }
        if ($update->bookingDate !== null) {
            $payload['bookingDate'] = self::formatDateTime($update->bookingDate);
        }
        if ($update->transactionDate !== null) {
            $payload['transactionDate'] = self::formatDateTime($update->transactionDate);
        }
        if ($update->purpose !== null) {
            $payload['purpose'] = $update->purpose;
        }
        if ($update->amount !== null) {
            $payload['amount'] = $update->amount;
        }
        if ($update->financialAccountId !== null) {
            $payload['financialAccountId'] = $update->financialAccountId;
        }
        if ($update->externalReference !== null) {
            $payload['externalReference'] = $update->externalReference;
        }
        if ($update->additionalInfo !== null) {
            $payload['additionalInfo'] = $update->additionalInfo;
        }
        if ($update->recipientOrSenderName !== null) {
            $payload['recipientOrSenderName'] = $update->recipientOrSenderName;
        }
        if ($update->recipientOrSenderEmail !== null) {
            $payload['recipientOrSenderEmail'] = $update->recipientOrSenderEmail;
        }
        if ($update->recipientOrSenderIban !== null) {
            $payload['recipientOrSenderIban'] = $update->recipientOrSenderIban;
        }
        if ($update->recipientOrSenderBic !== null) {
            $payload['recipientOrSenderBic'] = $update->recipientOrSenderBic;
        }
        if ($update->feeAmount !== null) {
            $payload['feeAmount'] = $update->feeAmount;
        }
        if ($update->feeTaxRatePercentage !== null) {
            $payload['feeTaxRatePercentage'] = $update->feeTaxRatePercentage;
        }
        if ($update->feePostingCategoryId !== null) {
            $payload['feePostingCategoryId'] = $update->feePostingCategoryId;
        }

        return new JsonBody(JsonCodec::encode($payload));
    }

    private static function formatDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:sP');
    }
}


