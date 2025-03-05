<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Voucher implements \JsonSerializable
{
    private ?string $id = null;
    private ?string $organizationId = null;
    private int $version;
    private string $type;
    private ?string $voucherNumber = null;
    private ?string $voucherDate = null;
    private array $totalAmount = [];
    private ?array $taxAmount = null;
    private array $taxItems = [];
    private array $remark = [];
    private array $voucherItems = [];
    private ?array $files = null;
    private ?string $createdDate = null;
    private ?string $updatedDate = null;
    private ?array $address = null;
    private ?string $dueDate = null;
    private ?array $contact = null;

    /**
     * Erstellt ein Voucher-Objekt aus einem Array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $voucher = new self();

        if (isset($data['id'])) {
            $voucher->setId($data['id']);
        }

        if (isset($data['organizationId'])) {
            $voucher->setOrganizationId($data['organizationId']);
        }

        if (isset($data['version'])) {
            $voucher->setVersion($data['version']);
        }

        if (isset($data['type'])) {
            $voucher->setType($data['type']);
        }

        if (isset($data['voucherNumber'])) {
            $voucher->setVoucherNumber($data['voucherNumber']);
        }

        if (isset($data['voucherDate'])) {
            $voucher->setVoucherDate($data['voucherDate']);
        }

        if (isset($data['totalAmount'])) {
            $voucher->setTotalAmount($data['totalAmount']);
        }

        if (isset($data['taxAmount'])) {
            $voucher->setTaxAmount($data['taxAmount']);
        }

        if (isset($data['taxItems'])) {
            $voucher->setTaxItems($data['taxItems']);
        }

        if (isset($data['remark'])) {
            $voucher->setRemark($data['remark']);
        }

        if (isset($data['voucherItems'])) {
            $voucher->setVoucherItems($data['voucherItems']);
        }

        if (isset($data['files'])) {
            $voucher->setFiles($data['files']);
        }

        if (isset($data['createdDate'])) {
            $voucher->setCreatedDate($data['createdDate']);
        }

        if (isset($data['updatedDate'])) {
            $voucher->setUpdatedDate($data['updatedDate']);
        }

        if (isset($data['address'])) {
            $voucher->setAddress($data['address']);
        }

        if (isset($data['dueDate'])) {
            $voucher->setDueDate($data['dueDate']);
        }

        if (isset($data['contact'])) {
            $voucher->setContact($data['contact']);
        }

        return $voucher;
    }

    /**
     * Setzt die ID des Vouchers
     *
     * @param string $id
     * @return void
     */
    private function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Gibt die ID des Vouchers zurück
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Setzt die Organizations-ID
     *
     * @param string $organizationId
     * @return void
     */
    private function setOrganizationId(string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Gibt die Organizations-ID zurück
     *
     * @return string|null
     */
    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    /**
     * Setzt die Version
     *
     * @param int $version
     * @return void
     */
    private function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Gibt die Version zurück
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Setzt den Typ des Vouchers
     * (z.B. salesinvoice, purchaseinvoice, salescreditnote, purchasecreditnote, ...)
     *
     * @param string $type
     * @return void
     */
    private function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gibt den Typ des Vouchers zurück
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Setzt die Belegnummer
     *
     * @param string|null $voucherNumber
     * @return void
     */
    public function setVoucherNumber(?string $voucherNumber): void
    {
        $this->voucherNumber = $voucherNumber;
    }

    /**
     * Gibt die Belegnummer zurück
     *
     * @return string|null
     */
    public function getVoucherNumber(): ?string
    {
        return $this->voucherNumber;
    }

    /**
     * Setzt das Belegdatum
     *
     * @param string|null $voucherDate
     * @return void
     */
    public function setVoucherDate(?string $voucherDate): void
    {
        $this->voucherDate = $voucherDate;
    }

    /**
     * Gibt das Belegdatum zurück
     *
     * @return string|null
     */
    public function getVoucherDate(): ?string
    {
        return $this->voucherDate;
    }

    /**
     * Setzt den Gesamtbetrag
     *
     * @param array $totalAmount
     * @return void
     */
    public function setTotalAmount(array $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * Gibt den Gesamtbetrag zurück
     *
     * @return array
     */
    public function getTotalAmount(): array
    {
        return $this->totalAmount;
    }

    /**
     * Setzt den Steuerbetrag
     *
     * @param array|null $taxAmount
     * @return void
     */
    public function setTaxAmount(?array $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    /**
     * Gibt den Steuerbetrag zurück
     *
     * @return array|null
     */
    public function getTaxAmount(): ?array
    {
        return $this->taxAmount;
    }

    /**
     * Setzt die Steuerelemente
     *
     * @param array $taxItems
     * @return void
     */
    public function setTaxItems(array $taxItems): void
    {
        $this->taxItems = $taxItems;
    }

    /**
     * Gibt die Steuerelemente zurück
     *
     * @return array
     */
    public function getTaxItems(): array
    {
        return $this->taxItems;
    }

    /**
     * Setzt die Bemerkung
     *
     * @param array $remark
     * @return void
     */
    public function setRemark(array $remark): void
    {
        $this->remark = $remark;
    }

    /**
     * Gibt die Bemerkung zurück
     *
     * @return array
     */
    public function getRemark(): array
    {
        return $this->remark;
    }

    /**
     * Setzt die Belegpositionen
     *
     * @param array $voucherItems
     * @return void
     */
    public function setVoucherItems(array $voucherItems): void
    {
        $this->voucherItems = $voucherItems;
    }

    /**
     * Gibt die Belegpositionen zurück
     *
     * @return array
     */
    public function getVoucherItems(): array
    {
        return $this->voucherItems;
    }

    /**
     * Setzt die Dateien
     *
     * @param array|null $files
     * @return void
     */
    public function setFiles(?array $files): void
    {
        $this->files = $files;
    }

    /**
     * Gibt die Dateien zurück
     *
     * @return array|null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }

    /**
     * Setzt das Erstellungsdatum
     *
     * @param string|null $createdDate
     * @return void
     */
    private function setCreatedDate(?string $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    /**
     * Gibt das Erstellungsdatum zurück
     *
     * @return string|null
     */
    public function getCreatedDate(): ?string
    {
        return $this->createdDate;
    }

    /**
     * Setzt das Aktualisierungsdatum
     *
     * @param string|null $updatedDate
     * @return void
     */
    private function setUpdatedDate(?string $updatedDate): void
    {
        $this->updatedDate = $updatedDate;
    }

    /**
     * Gibt das Aktualisierungsdatum zurück
     *
     * @return string|null
     */
    public function getUpdatedDate(): ?string
    {
        return $this->updatedDate;
    }

    /**
     * Setzt die Adresse
     *
     * @param array|null $address
     * @return void
     */
    public function setAddress(?array $address): void
    {
        $this->address = $address;
    }

    /**
     * Gibt die Adresse zurück
     *
     * @return array|null
     */
    public function getAddress(): ?array
    {
        return $this->address;
    }

    /**
     * Setzt das Fälligkeitsdatum
     *
     * @param string|null $dueDate
     * @return void
     */
    public function setDueDate(?string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    /**
     * Gibt das Fälligkeitsdatum zurück
     *
     * @return string|null
     */
    public function getDueDate(): ?string
    {
        return $this->dueDate;
    }

    /**
     * Setzt den Kontakt
     *
     * @param array|null $contact
     * @return void
     */
    public function setContact(?array $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * Gibt den Kontakt zurück
     *
     * @return array|null
     */
    public function getContact(): ?array
    {
        return $this->contact;
    }

    /**
     * Konvertiert das Objekt in ein Array für die JSON-Serialisierung
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'voucherItems' => $this->voucherItems,
        ];

        if (!empty($this->version)) {
            $data['version'] = $this->version;
        }

        if ($this->id) {
            $data['id'] = $this->id;
        }

        if ($this->voucherNumber) {
            $data['voucherNumber'] = $this->voucherNumber;
        }

        if ($this->voucherDate) {
            $data['voucherDate'] = $this->voucherDate;
        }

        if (!empty($this->totalAmount)) {
            $data['totalAmount'] = $this->totalAmount;
        }

        if ($this->taxAmount) {
            $data['taxAmount'] = $this->taxAmount;
        }

        if (!empty($this->taxItems)) {
            $data['taxItems'] = $this->taxItems;
        }

        if (!empty($this->remark)) {
            $data['remark'] = $this->remark;
        }

        if ($this->files) {
            $data['files'] = $this->files;
        }

        if ($this->address) {
            $data['address'] = $this->address;
        }

        if ($this->dueDate) {
            $data['dueDate'] = $this->dueDate;
        }

        if ($this->contact) {
            $data['contact'] = $this->contact;
        }

        return $data;
    }
}