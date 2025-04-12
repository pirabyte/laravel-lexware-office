<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class NewFinancialTransaction implements \JsonSerializable
{
    private string $valueDate;

    private string $bookingDate;

    private string $transactiondate;

    private string $purpose;

    /**
     * @var float Format must be ##.00 (e.g. 119.00). The only supported currency is EUR.
     */
    private float $amount;

    private ?string $additionalInfo;

    private string $recipientOrSenderName;

    private string $recipientOrSenderIban;

    private string $recipientOrSenderBic;

    private string $financialAccountId;

    private string $externalReference;

    public function jsonSerialize(): mixed
    {
        return [
            'valueDate' => $this->valueDate,
            'bookingDate' => $this->bookingDate,
            'transactiondate' => $this->transactiondate,
            'purpose' => $this->purpose,
            'amount' => $this->amount,
            'additionalInfo' => $this->additionalInfo,
            'recipientOrSenderName' => $this->recipientOrSenderName,
            'recipientOrSenderIban' => $this->recipientOrSenderIban,
            'recipientOrSenderBic' => $this->recipientOrSenderBic,
            'financialAccountId' => $this->financialAccountId,
            'externalReference' => $this->externalReference,
        ];
    }

    public static function fromArray(array $data): NewFinancialTransaction
    {
        $transaction = new self();
        $transaction->valueDate = $data['valueDate'];
        $transaction->bookingDate = $data['bookingDate'];
        $transaction->transactiondate = $data['transactiondate'];
        $transaction->purpose = $data['purpose'];
        $transaction->amount = $data['amount'];
        $transaction->additionalInfo = $data['additionalInfo'];
        $transaction->recipientOrSenderName = $data['recipientOrSenderName'];
        $transaction->recipientOrSenderIban = $data['recipientOrSenderIban'];
        $transaction->recipientOrSenderBic = $data['recipientOrSenderBic'];
        $transaction->financialAccountId = $data['financialAccountId'];
        $transaction->externalReference = $data['externalReference'];

        return $transaction;
    }
}
