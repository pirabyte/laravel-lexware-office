<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class VoucherAssignment implements \JsonSerializable
{
    private string $voucherId;
    private ?string $voucherNumber = null;
    private ?string $voucherDescription = null;
    private ?string $voucherDate = null;
    private float $amount;
    private ?string $category = null;

    /**
     * Konstruktor mit den erforderlichen Feldern
     *
     * @param string $voucherId Die ID des Belegs
     * @param float $amount Der Betrag der Zuweisung
     */
    public function __construct(string $voucherId, float $amount)
    {
        $this->voucherId = $voucherId;
        $this->amount = $amount;
    }

    /**
     * Konvertiert ein Array in eine VoucherAssignment-Instanz
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        // Erforderliche Felder validieren
        if (!isset($data['voucherId']) || !isset($data['amount'])) {
            throw new \InvalidArgumentException('Fehlende erforderliche Felder für VoucherAssignment');
        }

        // Objekt erstellen
        $assignment = new self($data['voucherId'], (float)$data['amount']);

        // Optionale Felder setzen
        if (isset($data['voucherNumber'])) {
            $assignment->setVoucherNumber($data['voucherNumber']);
        }

        if (isset($data['voucherDescription'])) {
            $assignment->setVoucherDescription($data['voucherDescription']);
        }

        if (isset($data['voucherDate'])) {
            $assignment->setVoucherDate($data['voucherDate']);
        }

        if (isset($data['category'])) {
            $assignment->setCategory($data['category']);
        }

        return $assignment;
    }

    /**
     * Konvertiert die VoucherAssignment-Instanz in ein Array für JSON-Serialisierung
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'voucherId' => $this->voucherId,
            'amount' => $this->amount,
        ];

        // Optionale Felder hinzufügen
        if ($this->voucherNumber !== null) {
            $data['voucherNumber'] = $this->voucherNumber;
        }

        if ($this->voucherDescription !== null) {
            $data['voucherDescription'] = $this->voucherDescription;
        }

        if ($this->voucherDate !== null) {
            $data['voucherDate'] = $this->voucherDate;
        }

        if ($this->category !== null) {
            $data['category'] = $this->category;
        }

        return $data;
    }

    /**
     * Gibt die ID des Belegs zurück
     * @return string
     */
    public function getVoucherId(): string
    {
        return $this->voucherId;
    }

    /**
     * Gibt die Belegnummer zurück
     * @return string|null
     */
    public function getVoucherNumber(): ?string
    {
        return $this->voucherNumber;
    }

    /**
     * Gibt die Beschreibung des Belegs zurück
     * @return string|null
     */
    public function getVoucherDescription(): ?string
    {
        return $this->voucherDescription;
    }

    /**
     * Gibt das Datum des Belegs zurück
     * @return string|null
     */
    public function getVoucherDate(): ?string
    {
        return $this->voucherDate;
    }

    /**
     * Gibt den Betrag der Zuweisung zurück
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Gibt die Kategorie zurück
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * Setzt die Belegnummer
     * @param string|null $voucherNumber
     * @return $this
     */
    public function setVoucherNumber(?string $voucherNumber): self
    {
        $this->voucherNumber = $voucherNumber;
        return $this;
    }

    /**
     * Setzt die Beschreibung des Belegs
     * @param string|null $voucherDescription
     * @return $this
     */
    public function setVoucherDescription(?string $voucherDescription): self
    {
        $this->voucherDescription = $voucherDescription;
        return $this;
    }

    /**
     * Setzt das Datum des Belegs
     * @param string|null $voucherDate
     * @return $this
     */
    public function setVoucherDate(?string $voucherDate): self
    {
        $this->voucherDate = $voucherDate;
        return $this;
    }

    /**
     * Setzt den Betrag der Zuweisung
     * @param float $amount
     * @return $this
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Setzt die Kategorie
     * @param string|null $category
     * @return $this
     */
    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }
}