<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\TransactionState;

class FinancialTransaction implements \JsonSerializable
{
    // Read-only properties (from API responses)
    private ?string $financialTransactionId = null;
    private ?string $transactionDate = null;
    private ?float $openAmount = null;
    private ?string $amountAsString = null;
    private ?string $openAmountAsString = null;
    private ?TransactionState $state = null;
    private ?int $lockVersion = null;
    private ?string $createdDate = null;
    private ?string $lastModifiedDate = null;
    private ?string $endToEndId = null;
    private ?string $bookingText = null;
    private ?string $virtualAccountId = null;
    private ?bool $ignore = null;
    private ?string $ignoreReason = null;

    // Required properties for create/update
    private ?string $valueDate = null;
    private ?string $bookingDate = null;
    private ?string $purpose = null;
    private ?float $amount = null;
    private ?string $financialAccountId = null;

    // Optional properties
    private ?string $externalReference = null;
    private ?float $feeAmount = null;
    private ?float $feeTaxRatePercentage = null;
    private ?string $feePostingCategoryId = null;
    private ?string $additionalInfo = null;
    private ?string $recipientOrSenderName = null;
    private ?string $recipientOrSenderEmail = null;
    private ?string $recipientOrSenderIban = null;
    private ?string $recipientOrSenderBic = null;

    /**
     * Konstruktor
     */
    public function __construct() {}

    /**
     * Konvertiert ein Array in eine FinancialTransaction-Instanz
     *
     * @return static
     */
    public static function fromArray(array $data): self
    {
        // Objekt erstellen
        $transaction = new self;

        if (isset($data['financialAccountId'])) {
            $transaction->financialAccountId = $data['financialAccountId'];
        }

        // Handle both 'transactiondate' and 'transactionDate' keys
        if (isset($data['transactiondate'])) {
            $transaction->transactionDate = $data['transactiondate'];
        } elseif (isset($data['transactionDate'])) {
            $transaction->transactionDate = $data['transactionDate'];
        }

        if (isset($data['amount'])) {
            $transaction->amount = $data['amount'];
        }

        if (isset($data['purpose'])) {
            $transaction->purpose = $data['purpose'];
        }

        if (isset($data['bookingDate'])) {
            $transaction->bookingDate = $data['bookingDate'];
        }

        if (isset($data['valueDate'])) {
            $transaction->valueDate = $data['valueDate'];
        }

        if (isset($data['financialTransactionId'])) {
            $transaction->financialTransactionId = $data['financialTransactionId'];
        }

        if (isset($data['transactionDate'])) {
            $transaction->transactionDate = $data['transactionDate'];
        }

        if (isset($data['openAmount'])) {
            $transaction->openAmount = (float) $data['openAmount'];
        }

        if (isset($data['amountAsString'])) {
            $transaction->amountAsString = $data['amountAsString'];
        }

        if (isset($data['openAmountAsString'])) {
            $transaction->openAmountAsString = $data['openAmountAsString'];
        }

        if (isset($data['state'])) {
            $transaction->state = is_string($data['state'])
                ? TransactionState::from($data['state'])
                : $data['state'];
        }

        if (isset($data['lockVersion'])) {
            $transaction->lockVersion = (int) $data['lockVersion'];
        }

        if (isset($data['createdDate'])) {
            $transaction->createdDate = $data['createdDate'];
        }

        if (isset($data['lastModifiedDate'])) {
            $transaction->lastModifiedDate = $data['lastModifiedDate'];
        }

        if (isset($data['endToEndId'])) {
            $transaction->endToEndId = $data['endToEndId'];
        }

        if (isset($data['externalReference'])) {
            $transaction->externalReference = $data['externalReference'];
        }

        if (isset($data['feeAmount'])) {
            $transaction->setFeeAmount((float) $data['feeAmount']);
        }

        if (isset($data['feeTaxRatePercentage'])) {
            $transaction->setFeeTaxRatePercentage((float) $data['feeTaxRatePercentage']);
        }

        if (isset($data['feePostingCategoryId'])) {
            $transaction->setFeePostingCategoryId($data['feePostingCategoryId']);
        }

        if (isset($data['additionalInfo'])) {
            $transaction->setAdditionalInfo($data['additionalInfo']);
        }

        if (isset($data['recipientOrSenderName'])) {
            $transaction->setRecipientOrSenderName($data['recipientOrSenderName']);
        }

        if (isset($data['recipientOrSenderEmail'])) {
            $transaction->setRecipientOrSenderEmail($data['recipientOrSenderEmail']);
        }

        if (isset($data['recipientOrSenderIban'])) {
            $transaction->setRecipientOrSenderIban($data['recipientOrSenderIban']);
        }

        if (isset($data['recipientOrSenderBic'])) {
            $transaction->setRecipientOrSenderBic($data['recipientOrSenderBic']);
        }

        // Additional read-only fields from API responses
        if (isset($data['bookingText'])) {
            $transaction->bookingText = $data['bookingText'];
        }

        if (isset($data['virtualAccountId'])) {
            $transaction->virtualAccountId = $data['virtualAccountId'];
        }

        if (isset($data['ignore'])) {
            $transaction->ignore = (bool) $data['ignore'];
        }

        if (isset($data['ignoreReason'])) {
            $transaction->ignoreReason = $data['ignoreReason'];
        }

        return $transaction;
    }

    /**
     * Konvertiert die FinancialTransaction-Instanz in ein Array für JSON-Serialisierung
     */
    public function jsonSerialize(): array
    {
        $data = [];

        // Required fields for create/update
        if ($this->valueDate !== null) {
            $data['valueDate'] = $this->valueDate;
        }
        if ($this->bookingDate !== null) {
            $data['bookingDate'] = $this->bookingDate;
        }
        if ($this->purpose !== null) {
            $data['purpose'] = $this->purpose;
        }
        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }
        if ($this->financialAccountId !== null) {
            $data['financialAccountId'] = $this->financialAccountId;
        }

        // For create requests, use 'transactiondate' (lowercase)
        if ($this->transactionDate !== null && $this->financialTransactionId === null) {
            $data['transactiondate'] = $this->transactionDate;
        } elseif ($this->transactionDate !== null) {
            $data['transactionDate'] = $this->transactionDate;
        }

        if ($this->openAmount !== null) {
            $data['openAmount'] = $this->openAmount;
        }

        if ($this->amountAsString !== null) {
            $data['amountAsString'] = $this->amountAsString;
        }

        if ($this->openAmountAsString !== null) {
            $data['openAmountAsString'] = $this->openAmountAsString;
        }

        if ($this->state !== null) {
            $data['state'] = $this->state->value;
        }

        if ($this->lockVersion !== null) {
            $data['lockVersion'] = $this->lockVersion;
        }

        if ($this->createdDate !== null) {
            $data['createdDate'] = $this->createdDate;
        }

        if ($this->lastModifiedDate !== null) {
            $data['lastModifiedDate'] = $this->lastModifiedDate;
        }

        if ($this->endToEndId !== null) {
            $data['endToEndId'] = $this->endToEndId;
        }

        if ($this->externalReference !== null) {
            $data['externalReference'] = $this->externalReference;
        }

        if ($this->feeAmount !== null) {
            $data['feeAmount'] = $this->feeAmount;
        }

        if ($this->feeTaxRatePercentage !== null) {
            $data['feeTaxRatePercentage'] = $this->feeTaxRatePercentage;
        }

        if ($this->feePostingCategoryId !== null) {
            $data['feePostingCategoryId'] = $this->feePostingCategoryId;
        }

        if ($this->additionalInfo !== null) {
            $data['additionalInfo'] = $this->additionalInfo;
        }

        if ($this->recipientOrSenderName !== null) {
            $data['recipientOrSenderName'] = $this->recipientOrSenderName;
        }

        if ($this->recipientOrSenderEmail !== null) {
            $data['recipientOrSenderEmail'] = $this->recipientOrSenderEmail;
        }

        if ($this->recipientOrSenderIban !== null) {
            $data['recipientOrSenderIban'] = $this->recipientOrSenderIban;
        }

        if ($this->recipientOrSenderBic !== null) {
            $data['recipientOrSenderBic'] = $this->recipientOrSenderBic;
        }

        return $data;
    }

    // Getter-Methoden für alle Properties

    /**
     * Gibt die eindeutige ID der Transaktion zurück
     */
    public function getFinancialTransactionId(): ?string
    {
        return $this->financialTransactionId;
    }

    /**
     * Setzt die eindeutige ID der Transaktion
     *
     * @return $this
     */
    public function setFinancialTransactionId(?string $financialTransactionId): self
    {
        $this->financialTransactionId = $financialTransactionId;

        return $this;
    }

    /**
     * Gibt das Transaktionsdatum zurück
     */
    public function getTransactionDate(): ?string
    {
        return $this->transactionDate;
    }

    /**
     * Gibt den offenen Betrag zurück
     */
    public function getOpenAmount(): ?float
    {
        return $this->openAmount;
    }

    /**
     * Gibt den Betrag als String zurück
     */
    public function getAmountAsString(): ?string
    {
        return $this->amountAsString;
    }

    /**
     * Gibt den offenen Betrag als String zurück
     */
    public function getOpenAmountAsString(): ?string
    {
        return $this->openAmountAsString;
    }

    /**
     * Gibt den Status der Transaktion zurück
     */
    public function getState(): ?TransactionState
    {
        return $this->state;
    }

    /**
     * Gibt die Versionsnummer zurück
     */
    public function getLockVersion(): ?int
    {
        return $this->lockVersion;
    }

    /**
     * Gibt das Erstellungsdatum zurück
     */
    public function getCreatedDate(): ?string
    {
        return $this->createdDate;
    }

    /**
     * Gibt das Datum der letzten Änderung zurück
     */
    public function getLastModifiedDate(): ?string
    {
        return $this->lastModifiedDate;
    }

    /**
     * Gibt die End-to-End-ID zurück
     */
    public function getEndToEndId(): ?string
    {
        return $this->endToEndId;
    }

    /**
     * Gibt das Wertstellungsdatum zurück
     */
    public function getValueDate(): ?string
    {
        return $this->valueDate;
    }

    /**
     * Gibt das Buchungsdatum zurück
     */
    public function getBookingDate(): ?string
    {
        return $this->bookingDate;
    }

    /**
     * Gibt die externe Referenz zurück
     */
    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    /**
     * Gibt den Verwendungszweck zurück
     */
    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    /**
     * Gibt den Betrag zurück
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Gibt den Gebührenbetrag zurück
     */
    public function getFeeAmount(): ?float
    {
        return $this->feeAmount;
    }

    /**
     * Gibt den Steuersatz für die Gebühr zurück
     */
    public function getFeeTaxRatePercentage(): ?float
    {
        return $this->feeTaxRatePercentage;
    }

    /**
     * Gibt die Buchungskategorie-ID für die Gebühr zurück
     */
    public function getFeePostingCategoryId(): ?string
    {
        return $this->feePostingCategoryId;
    }

    /**
     * Gibt zusätzliche Informationen zurück
     */
    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    /**
     * Gibt den Namen des Empfängers oder Absenders zurück
     */
    public function getRecipientOrSenderName(): ?string
    {
        return $this->recipientOrSenderName;
    }

    /**
     * Gibt die E-Mail-Adresse des Empfängers oder Absenders zurück
     */
    public function getRecipientOrSenderEmail(): ?string
    {
        return $this->recipientOrSenderEmail;
    }

    /**
     * Gibt die IBAN des Empfängers oder Absenders zurück
     */
    public function getRecipientOrSenderIban(): ?string
    {
        return $this->recipientOrSenderIban;
    }

    /**
     * Gibt die BIC des Empfängers oder Absenders zurück
     */
    public function getRecipientOrSenderBic(): ?string
    {
        return $this->recipientOrSenderBic;
    }

    /**
     * Gibt die Finanzkonto-ID zurück
     */
    public function getFinancialAccountId(): ?string
    {
        return $this->financialAccountId;
    }

    // Setter-Methoden für die veränderbaren Properties

    /**
     * Setzt den Verwendungszweck
     *
     * @return $this
     */
    public function setPurpose(string $purpose): self
    {
        $this->purpose = $purpose;

        return $this;
    }

    /**
     * Setzt den Betrag
     *
     * @return $this
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Setzt den Gebührenbetrag
     *
     * @return $this
     */
    public function setFeeAmount(?float $feeAmount): self
    {
        $this->feeAmount = $feeAmount;

        return $this;
    }

    /**
     * Setzt den Steuersatz für die Gebühr
     *
     * @return $this
     */
    public function setFeeTaxRatePercentage(?float $feeTaxRatePercentage): self
    {
        $this->feeTaxRatePercentage = $feeTaxRatePercentage;

        return $this;
    }

    /**
     * Setzt die Buchungskategorie-ID für die Gebühr
     *
     * @return $this
     */
    public function setFeePostingCategoryId(?string $feePostingCategoryId): self
    {
        $this->feePostingCategoryId = $feePostingCategoryId;

        return $this;
    }

    /**
     * Setzt zusätzliche Informationen
     *
     * @return $this
     */
    public function setAdditionalInfo(?string $additionalInfo): self
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    /**
     * Setzt den Namen des Empfängers oder Absenders
     *
     * @return $this
     */
    public function setRecipientOrSenderName(?string $recipientOrSenderName): self
    {
        $this->recipientOrSenderName = $recipientOrSenderName;

        return $this;
    }

    /**
     * Setzt die E-Mail-Adresse des Empfängers oder Absenders
     *
     * @return $this
     */
    public function setRecipientOrSenderEmail(?string $recipientOrSenderEmail): self
    {
        $this->recipientOrSenderEmail = $recipientOrSenderEmail;

        return $this;
    }

    /**
     * Setzt die IBAN des Empfängers oder Absenders
     *
     * @return $this
     */
    public function setRecipientOrSenderIban(?string $recipientOrSenderIban): self
    {
        $this->recipientOrSenderIban = $recipientOrSenderIban;

        return $this;
    }

    /**
     * Setzt die BIC des Empfängers oder Absenders
     *
     * @return $this
     */
    public function setRecipientOrSenderBic(?string $recipientOrSenderBic): self
    {
        $this->recipientOrSenderBic = $recipientOrSenderBic;

        return $this;
    }

    /**
     * Setzt die Finanzkonto-ID
     *
     * @return $this
     */
    public function setFinancialAccountId(string $financialAccountId): self
    {
        $this->financialAccountId = $financialAccountId;

        return $this;
    }

    public function setValueDate(?string $valueDate): void
    {
        $this->valueDate = $valueDate;
    }

    public function setBookingDate(?string $bookingDate): void
    {
        $this->bookingDate = $bookingDate;
    }

    public function setTransactionDate(?string $transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    /**
     * Setzt die Versionsnummer (für optimistic locking)
     *
     * @return $this
     */
    public function setLockVersion(?int $lockVersion): self
    {
        $this->lockVersion = $lockVersion;

        return $this;
    }

    /**
     * Gibt den Buchungstext zurück
     */
    public function getBookingText(): ?string
    {
        return $this->bookingText;
    }

    /**
     * Gibt die virtuelle Konto-ID zurück
     */
    public function getVirtualAccountId(): ?string
    {
        return $this->virtualAccountId;
    }

    /**
     * Gibt zurück, ob die Transaktion ignoriert wird
     */
    public function getIgnore(): ?bool
    {
        return $this->ignore;
    }

    /**
     * Gibt den Grund für das Ignorieren zurück
     */
    public function getIgnoreReason(): ?string
    {
        return $this->ignoreReason;
    }
}
