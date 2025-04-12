<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class VoucherItem implements \JsonSerializable
{
    private ?string $id = null;

    private ?string $type = null;

    private ?string $name = null;

    private ?string $description = null;

    private ?int $quantity = null;

    private ?string $unitName = null;

    private ?array $unitPrice = null;

    private ?array $totalPrice = null;

    private ?string $vatRateType = null;

    private ?float $vatRatePercent = null;

    private ?string $categoryId = null;

    private ?float $amount = null;

    private ?float $taxAmount = null;

    private ?float $taxRatePercent = null;

    /**
     * Erstellt ein VoucherItem-Objekt aus einem Array
     */
    public static function fromArray(array $data): self
    {
        $item = new self();

        if (isset($data['id'])) {
            $item->setId($data['id']);
        }

        if (isset($data['type'])) {
            $item->setType($data['type']);
        }

        if (isset($data['name'])) {
            $item->setName($data['name']);
        }

        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }

        if (isset($data['quantity'])) {
            $item->setQuantity($data['quantity']);
        }

        if (isset($data['amount'])) {
            $item->setAmount($data['amount']);
        }

        if (isset($data['taxAmount'])) {
            $item->setTaxAmount($data['taxAmount']);
        }

        if (isset($data['taxRatePercent'])) {
            $item->setTaxRatePercent($data['taxRatePercent']);
        }

        if (isset($data['unitName'])) {
            $item->setUnitName($data['unitName']);
        }

        if (isset($data['unitPrice'])) {
            $item->setUnitPrice($data['unitPrice']);
        }

        if (isset($data['totalPrice'])) {
            $item->setTotalPrice($data['totalPrice']);
        }

        if (isset($data['vatRateType'])) {
            $item->setVatRateType($data['vatRateType']);
        }

        if (isset($data['vatRatePercent'])) {
            $item->setVatRatePercent($data['vatRatePercent']);
        }

        if (isset($data['categoryId'])) {
            $item->setCategoryId($data['categoryId']);
        }

        return $item;
    }

    /**
     * Setzt die ID des VoucherItems
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Gibt die ID des VoucherItems zurück
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Setzt den Typ des VoucherItems
     * (z.B. custom, text, service, material, product)
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gibt den Typ des VoucherItems zurück
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Setzt den Namen des VoucherItems
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gibt den Namen des VoucherItems zurück
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Setzt die Beschreibung des VoucherItems
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Gibt die Beschreibung des VoucherItems zurück
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Setzt die Menge des VoucherItems
     */
    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * Gibt die Menge des VoucherItems zurück
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * Setzt den Namen der Einheit des VoucherItems
     */
    public function setUnitName(?string $unitName): void
    {
        $this->unitName = $unitName;
    }

    /**
     * Gibt den Namen der Einheit des VoucherItems zurück
     */
    public function getUnitName(): ?string
    {
        return $this->unitName;
    }

    /**
     * Setzt den Einheitspreis des VoucherItems
     */
    public function setUnitPrice(?array $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    /**
     * Gibt den Einheitspreis des VoucherItems zurück
     */
    public function getUnitPrice(): ?array
    {
        return $this->unitPrice;
    }

    /**
     * Setzt den Gesamtpreis des VoucherItems
     */
    public function setTotalPrice(?array $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * Gibt den Gesamtpreis des VoucherItems zurück
     */
    public function getTotalPrice(): ?array
    {
        return $this->totalPrice;
    }

    /**
     * Setzt den Typ des Mehrwertsteuersatzes
     * (z.B. normal, reduced, custom, ...)
     */
    public function setVatRateType(?string $vatRateType): void
    {
        $this->vatRateType = $vatRateType;
    }

    /**
     * Gibt den Typ des Mehrwertsteuersatzes zurück
     */
    public function getVatRateType(): ?string
    {
        return $this->vatRateType;
    }

    /**
     * Setzt den Prozentsatz des Mehrwertsteuersatzes
     */
    public function setVatRatePercent(?float $vatRatePercent): void
    {
        $this->vatRatePercent = $vatRatePercent;
    }

    /**
     * Gibt den Prozentsatz des Mehrwertsteuersatzes zurück
     */
    public function getVatRatePercent(): ?float
    {
        return $this->vatRatePercent;
    }

    /**
     * Setzt die Kategorie-ID des VoucherItems
     */
    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Gibt die Kategorie-ID des VoucherItems zurück
     */
    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    /**
     * Konvertiert das Objekt in ein Array für die JSON-Serialisierung
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->id) {
            $data['id'] = $this->id;
        }

        if ($this->type) {
            $data['type'] = $this->type;
        }

        if ($this->name) {
            $data['name'] = $this->name;
        }

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->quantity !== null) {
            $data['quantity'] = $this->quantity;
        }

        if ($this->unitName) {
            $data['unitName'] = $this->unitName;
        }

        if ($this->unitPrice) {
            $data['unitPrice'] = $this->unitPrice;
        }

        if ($this->totalPrice) {
            $data['totalPrice'] = $this->totalPrice;
        }

        if ($this->vatRateType) {
            $data['vatRateType'] = $this->vatRateType;
        }

        if ($this->vatRatePercent !== null) {
            $data['vatRatePercent'] = $this->vatRatePercent;
        }

        if ($this->categoryId) {
            $data['categoryId'] = $this->categoryId;
        }

        if (isset($this->taxAmount)) {
            $data['taxAmount'] = $this->taxAmount;
        }

        if (isset($this->amount)) {
            $data['amount'] = $this->amount;
        }

        return $data;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getTaxAmount(): ?float
    {
        return $this->taxAmount;
    }

    public function getTaxRatePercent(): ?float
    {
        return $this->taxRatePercent;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function setTaxRatePercent(float $taxRatePercent): void
    {
        $this->taxRatePercent = $taxRatePercent;
    }

    public function setTaxAmount(float $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }
}
