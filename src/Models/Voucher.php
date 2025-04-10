<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Voucher implements \JsonSerializable
{
    #region Properties
    /**
     * Die ID des Vouchers.
     */
    private ?string $id = null;

    /**
     * Die Organizations-ID des Vouchers.
     */
    private ?string $organizationId = null;

    /**
     * Die Version des Vouchers.
     */
    private int $version;

    /**
     * Der Typ des Vouchers (z.B. salesinvoice, purchaseinvoice, salescreditnote, purchasecreditnote, ...).
     */
    private string $type;

    /**
     * Die Belegnummer des Vouchers.
     */
    private ?string $voucherNumber = null;

    /**
     * Das Belegdatum des Vouchers.
     */
    private ?string $voucherDate = null;

    /**
     * Der Gesamtbetrag (brutto) des Vouchers.
     */
    private ?float $totalGrossAmount;

    /**
     * Der Steuerbetrag des Vouchers.
     */
    private ?float $totalTaxAmount = null;

    /**
     * Der Steuertyp des Vouchers (brutto/netto).
     */
    private string $taxType = 'gross';

    /**
     * Die Bemerkung zum Voucher.
     */
    private ?string $remark;

    /**
     * Die Belegpositionen des Vouchers.
     *
     * @var array<int, array>
     */
    private array $voucherItems = [];

    /**
     * Gibt an, ob ein Sammelkontakt verwendet werden soll.
     */
    private bool $useCollectiveContact = false;

    /**
     * Der Status des Vouchers (open, paid, cancelled, ...).
     */
    private string $voucherStatus = 'open';

    /**
     * Die Dateien des Vouchers.
     *
     * @var array<int, mixed>|null
     */
    private ?array $files = null;

    /**
     * Das Erstellungsdatum des Vouchers.
     */
    private ?string $createdDate = null;

    /**
     * Das Aktualisierungsdatum des Vouchers.
     */
    private ?string $updatedDate = null;

    /**
     * Das Fälligkeitsdatum des Vouchers.
     */
    private ?string $dueDate = null;

    /**
     * Die Kontakt-ID des Vouchers.
     */
    private ?string $contactId = null;
    #endregion

    #region Factory Methods
    private ?string $shippingDate = null;

    /**
     * Erstellt ein Voucher-Objekt aus einem Array.
     *
     * @param array<string, mixed> $data Die Daten aus denen das Voucher-Objekt erstellt werden soll
     * @return self Das erstellte Voucher-Objekt
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

        if (isset($data['taxType'])) {
            $voucher->setTaxType($data['taxType']);
        }

        if (isset($data['voucherNumber'])) {
            $voucher->setVoucherNumber($data['voucherNumber']);
        }

        if (isset($data['shippingDate'])) {
            $voucher->setShippingDate($data['shippingDate']);
        }

        if (isset($data['voucherDate'])) {
            $voucher->setVoucherDate($data['voucherDate']);
        }

        if (isset($data['totalGrossAmount'])) {
            $voucher->setTotalGrossAmount($data['totalGrossAmount']);
        }

        if (isset($data['totalTaxAmount'])) {
            $voucher->setTotalTaxAmount($data['totalTaxAmount']);
        }

        if (isset($data['useCollectiveContact'])) {
            $voucher->setUseCollectiveContact($data['useCollectiveContact']);
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

        if (isset($data['voucherStatus'])) {
            $voucher->setVoucherStatus($data['voucherStatus']);
        }

        if (isset($data['dueDate'])) {
            $voucher->setDueDate($data['dueDate']);
        }

        if (isset($data['contactId'])) {
            $voucher->setContactId($data['contactId']);
        }

        return $voucher;
    }
    #endregion

    #region ID Methods
    /**
     * Setzt die ID des Vouchers.
     *
     * @param string $id Die ID des Vouchers
     * @return void
     */
    private function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Gibt die ID des Vouchers zurück.
     *
     * @return string|null Die ID des Vouchers oder null, falls keine ID gesetzt ist
     */
    public function getId(): ?string
    {
        return $this->id;
    }
    #endregion

    #region Organization Methods
    /**
     * Setzt die Organizations-ID.
     *
     * @param string $organizationId Die Organizations-ID
     * @return void
     */
    private function setOrganizationId(string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Gibt die Organizations-ID zurück.
     *
     * @return string|null Die Organizations-ID oder null, falls keine ID gesetzt ist
     */
    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }
    #endregion

    #region Version Methods
    /**
     * Setzt die Version.
     *
     * @param int $version Die Version
     * @return void
     */
    private function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Gibt die Version zurück.
     *
     * @return int Die Version
     */
    public function getVersion(): int
    {
        return $this->version;
    }
    #endregion

    #region Type Methods
    /**
     * Setzt den Typ des Vouchers
     * (z.B. salesinvoice, purchaseinvoice, salescreditnote, purchasecreditnote, ...).
     *
     * @param string $type Der Typ des Vouchers
     * @return void
     */
    private function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gibt den Typ des Vouchers zurück.
     *
     * @return string Der Typ des Vouchers
     */
    public function getType(): string
    {
        return $this->type;
    }
    #endregion

    #region Voucher Number Methods
    /**
     * Setzt die Belegnummer.
     *
     * @param string|null $voucherNumber Die Belegnummer oder null
     * @return void
     */
    public function setVoucherNumber(?string $voucherNumber): void
    {
        $this->voucherNumber = $voucherNumber;
    }

    /**
     * Gibt die Belegnummer zurück.
     *
     * @return string|null Die Belegnummer oder null
     */
    public function getVoucherNumber(): ?string
    {
        return $this->voucherNumber;
    }
    #endregion

    #region Voucher Date Methods
    /**
     * Setzt das Belegdatum.
     *
     * @param string|null $voucherDate Das Belegdatum im Format YYYY-MM-DD oder null
     * @return void
     */
    public function setVoucherDate(?string $voucherDate): void
    {
        $this->voucherDate = $voucherDate;
    }

    /**
     * Gibt das Belegdatum zurück.
     *
     * @return string|null Das Belegdatum oder null
     */
    public function getVoucherDate(): ?string
    {
        return $this->voucherDate;
    }
    #endregion

    #region Amount Methods
    /**
     * Setzt den Gesamtbetrag (brutto).
     *
     * @param float $totalGrossAmount Der Gesamtbetrag
     * @return void
     */
    public function setTotalGrossAmount(float $totalGrossAmount): void
    {
        $this->totalGrossAmount = $totalGrossAmount;
    }

    /**
     * Gibt den Gesamtbetrag (brutto) zurück.
     *
     * @return float|null Der Gesamtbetrag oder null
     */
    public function getTotalGrossAmount(): ?float
    {
        return $this->totalGrossAmount;
    }

    /**
     * Setzt den Steuerbetrag.
     *
     * @param float|null $totalTaxAmount Der Steuerbetrag oder null
     * @return void
     */
    public function setTotalTaxAmount(?float $totalTaxAmount): void
    {
        $this->totalTaxAmount = $totalTaxAmount;
    }

    /**
     * Gibt den Steuerbetrag zurück.
     *
     * @return float|null Der Steuerbetrag oder null
     */
    public function getTotalTaxAmount(): ?float
    {
        return $this->totalTaxAmount;
    }
    #endregion

    #region Tax Type Methods
    /**
     * Setzt den Steuertyp (brutto/netto).
     *
     * @param string $taxType Der Steuertyp
     * @return void
     */
    private function setTaxType(string $taxType): void
    {
        $this->taxType = $taxType;
    }

    /**
     * Gibt den Steuertyp zurück.
     *
     * @return string Der Steuertyp
     */
    public function getTaxType(): string
    {
        return $this->taxType;
    }
    #endregion

    #region Remark Methods
    /**
     * Setzt die Bemerkung.
     *
     * @param string|null $remark Die Bemerkung oder null
     * @return void
     */
    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    /**
     * Gibt die Bemerkung zurück.
     *
     * @return string|null Die Bemerkung oder null
     */
    public function getRemark(): ?string
    {
        return $this->remark;
    }
    #endregion

    #region Voucher Items Methods
    /**
     * Setzt die Belegpositionen.
     *
     * @param array<VoucherItem> $voucherItems Die Belegpositionen
     * @return void
     */
    public function setVoucherItems(array $voucherItems): void
    {
        $this->voucherItems = $voucherItems;
    }

    /**
     * Gibt die Belegpositionen zurück.
     *
     * @return array<int, array> Die Belegpositionen
     */
    public function getVoucherItems(): array
    {
        return $this->voucherItems;
    }
    #endregion

    #region Files Methods
    /**
     * Setzt die Dateien.
     *
     * @param array<int, mixed>|null $files Die Dateien oder null
     * @return void
     */
    public function setFiles(?array $files): void
    {
        $this->files = $files;
    }

    /**
     * Gibt die Dateien zurück.
     *
     * @return array<int, mixed>|null Die Dateien oder null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }
    #endregion

    #region Date Methods
    /**
     * Setzt das Erstellungsdatum.
     *
     * @param string|null $createdDate Das Erstellungsdatum oder null
     * @return void
     */
    private function setCreatedDate(?string $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    /**
     * Gibt das Erstellungsdatum zurück.
     *
     * @return string|null Das Erstellungsdatum oder null
     */
    public function getCreatedDate(): ?string
    {
        return $this->createdDate;
    }

    /**
     * Setzt das Aktualisierungsdatum.
     *
     * @param string|null $updatedDate Das Aktualisierungsdatum oder null
     * @return void
     */
    private function setUpdatedDate(?string $updatedDate): void
    {
        $this->updatedDate = $updatedDate;
    }

    /**
     * Gibt das Aktualisierungsdatum zurück.
     *
     * @return string|null Das Aktualisierungsdatum oder null
     */
    public function getUpdatedDate(): ?string
    {
        return $this->updatedDate;
    }

    /**
     * Setzt das Fälligkeitsdatum.
     *
     * @param string|null $dueDate Das Fälligkeitsdatum oder null
     * @return void
     */
    public function setDueDate(?string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    /**
     * Gibt das Fälligkeitsdatum zurück.
     *
     * @return string|null Das Fälligkeitsdatum oder null
     */
    public function getDueDate(): ?string
    {
        return $this->dueDate;
    }
    #endregion

    #region Contact Methods
    /**
     * Setzt die Kontakt-ID.
     *
     * @param string|null $contactId Die Kontakt-ID oder null
     * @return void
     */
    public function setContactId(?string $contactId): void
    {
        $this->contactId = $contactId;
    }

    /**
     * Gibt die Kontakt-ID zurück.
     *
     * @return string|null Die Kontakt-ID oder null
     */
    public function getContactId(): ?string
    {
        return $this->contactId;
    }
    #endregion

    #region Status Methods
    /**
     * Setzt den Status des Vouchers.
     *
     * @param string $voucherStatus Der Status des Vouchers
     * @return void
     */
    private function setVoucherStatus(string $voucherStatus): void
    {
        $this->voucherStatus = $voucherStatus;
    }

    /**
     * Gibt den Status des Vouchers zurück.
     *
     * @return string Der Status des Vouchers
     */
    public function getVoucherStatus(): string
    {
        return $this->voucherStatus;
    }
    #endregion

    #region Collective Contact Methods
    /**
     * Setzt, ob ein Sammelkontakt verwendet werden soll.
     *
     * @param bool $useCollectiveContact Gibt an, ob ein Sammelkontakt verwendet werden soll
     * @return void
     */
    private function setUseCollectiveContact(bool $useCollectiveContact): void
    {
        $this->useCollectiveContact = $useCollectiveContact;
    }

    /**
     * Gibt zurück, ob ein Sammelkontakt verwendet werden soll.
     *
     * @return bool True, wenn ein Sammelkontakt verwendet werden soll, sonst false
     */
    public function getUseCollectiveContact(): bool
    {
        return $this->useCollectiveContact;
    }
    #endregion

    #region Serialization
    /**
     * Konvertiert das Objekt in ein Array für die JSON-Serialisierung.
     *
     * @return array<string, mixed> Das Array für die JSON-Serialisierung
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if (isset($this->type)) {
            $data['type'] = $this->type;
        }



        if (!empty($this->version)) {
            $data['version'] = $this->version;
        }

        if ($this->id) {
            $data['id'] = $this->id;
        }

        if ($this->organizationId) {
            $data['organizationId'] = $this->organizationId;
        }

        if ($this->voucherNumber) {
            $data['voucherNumber'] = $this->voucherNumber;
        }

        if ($this->voucherDate) {
            $data['voucherDate'] = $this->voucherDate;
        }

        if ($this->shippingDate) {
            $data['shippingDate'] = $this->shippingDate;
        }

        if (!empty($this->totalGrossAmount)) {
            $data['totalGrossAmount'] = $this->totalGrossAmount;
        }

        if ( isset($this->totalTaxAmount)) {
            $data['totalTaxAmount'] = $this->totalTaxAmount;
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

        if($this->taxType) {
            $data['taxType'] = $this->taxType;
        }

        if ($this->voucherStatus) {
            $data['voucherStatus'] = $this->voucherStatus;
        }

        if(isset($this->useCollectiveContact)) {
            $data['useCollectiveContact'] = $this->useCollectiveContact;
        }

        if (isset($this->files)) {
            $data['files'] = $this->files;
        }

        if (isset($this->createdDate)) {
            $data['createdDate'] = $this->createdDate;
        }

        if (isset($this->updatedDate)) {
            $data['updatedDate'] = $this->updatedDate;
        }

        if ($this->dueDate) {
            $data['dueDate'] = $this->dueDate;
        }

        if ($this->contactId) {
            $data['contactId'] = $this->contactId;
        }

        if (isset($this->voucherItems)) {
            $data['voucherItems'] = $this->voucherItems;
        }

        return $data;
    }
    #endregion
    public function getShippingDate()
    {
        return $this->shippingDate;
    }

    private function setShippingDate(mixed $shippingDate)
    {
        $this->shippingDate = $shippingDate;
    }
}
