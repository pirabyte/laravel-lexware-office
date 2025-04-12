<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\AccountSystem;
use Pirabyte\LaravelLexwareOffice\Enums\CreditCardProvider;
use Pirabyte\LaravelLexwareOffice\Enums\FinancialAccountType;

class FinancialAccount implements \JsonSerializable
{
    // Read-only properties
    private string $financialAccountId;

    private ?string $createdDate = null;

    private ?string $lastModifiedDate = null;

    private ?int $lockVersion = null;

    private bool $deactivated = false;

    private ?object $state = null;

    private ?string $initialSyncDate = null;

    // Immutable property
    private AccountSystem $accountSystem;

    // Mutable properties
    private FinancialAccountType $type;

    private string $name;

    private ?string $bankName = null;

    private ?string $accountHolder = null;

    private ?float $balance = null;

    private ?string $externalReference = null;

    private ?string $iban = null;

    private ?string $bic = null;

    private ?CreditCardProvider $provider = null;

    private ?string $creditCardNumber = null;

    /**
     * Konstruktor mit minimal erforderlichen Feldern
     *
     * @param  string  $financialAccountId  Unique ID des Finanzkontos
     * @param  FinancialAccountType  $type  Typ des Finanzkontos
     * @param  AccountSystem  $accountSystem  Beschreibung des Finanzkontos
     * @param  string  $name  Individueller Name des Finanzkontos
     */
    public function __construct(
        string $financialAccountId,
        FinancialAccountType $type,
        AccountSystem $accountSystem,
        string $name
    ) {
        $this->financialAccountId = $financialAccountId;
        $this->type = $type;
        $this->accountSystem = $accountSystem;
        $this->name = $name;
    }

    /**
     * Konvertiert ein Array in eine FinancialAccount-Instanz
     *
     * @return static
     */
    public static function fromArray(array $data): self
    {
        // Erforderliche Felder validieren
        if (! isset($data['financialAccountId']) || ! isset($data['type']) || ! isset($data['accountSystem']) || ! isset($data['name'])) {
            throw new \InvalidArgumentException('Fehlende erforderliche Felder für FinancialAccount');
        }

        // Type und AccountSystem als Enums verarbeiten
        $type = is_string($data['type'])
            ? FinancialAccountType::from($data['type'])
            : $data['type'];

        $accountSystem = is_string($data['accountSystem'])
            ? AccountSystem::from($data['accountSystem'])
            : $data['accountSystem'];

        // Objekt erstellen
        $account = new self($data['financialAccountId'], $type, $accountSystem, $data['name']);

        // Optionale Felder setzen
        if (isset($data['createdDate'])) {
            $account->createdDate = $data['createdDate'];
        }

        if (isset($data['lastModifiedDate'])) {
            $account->lastModifiedDate = $data['lastModifiedDate'];
        }

        if (isset($data['lockVersion'])) {
            $account->lockVersion = $data['lockVersion'];
        }

        if (isset($data['deactivated'])) {
            $account->deactivated = $data['deactivated'];
        }

        if (isset($data['state'])) {
            $account->state = $data['state'];
        }

        if (isset($data['initialSyncDate'])) {
            $account->initialSyncDate = $data['initialSyncDate'];
        }

        if (isset($data['bankName'])) {
            $account->setBankName($data['bankName']);
        }

        if (isset($data['accountHolder'])) {
            $account->setAccountHolder($data['accountHolder']);
        }

        if (isset($data['balance'])) {
            $account->setBalance($data['balance']);
        }

        if (isset($data['externalReference'])) {
            $account->setExternalReference($data['externalReference']);
        }

        if (isset($data['iban'])) {
            $account->setIban($data['iban']);
        }

        if (isset($data['bic'])) {
            $account->setBic($data['bic']);
        }

        if (isset($data['provider'])) {
            $provider = is_string($data['provider'])
                ? CreditCardProvider::from($data['provider'])
                : $data['provider'];
            $account->setProvider($provider);
        }

        if (isset($data['creditCardNumber'])) {
            $account->setCreditCardNumber($data['creditCardNumber']);
        }

        return $account;
    }

    /**
     * Konvertiert die FinancialAccount-Instanz in ein Array für JSON-Serialisierung
     */
    public function jsonSerialize(): array
    {
        $data = [
            'financialAccountId' => $this->financialAccountId,
            'type' => $this->type->value,
            'accountSystem' => $this->accountSystem->value,
            'name' => $this->name,
        ];

        // Nur nicht-null Werte hinzufügen
        if ($this->createdDate !== null) {
            $data['createdDate'] = $this->createdDate;
        }

        if ($this->lastModifiedDate !== null) {
            $data['lastModifiedDate'] = $this->lastModifiedDate;
        }

        if ($this->lockVersion !== null) {
            $data['lockVersion'] = $this->lockVersion;
        }

        if ($this->deactivated) {
            $data['deactivated'] = $this->deactivated;
        }

        if ($this->state !== null) {
            $data['state'] = $this->state;
        }

        if ($this->initialSyncDate !== null) {
            $data['initialSyncDate'] = $this->initialSyncDate;
        }

        if ($this->bankName !== null) {
            $data['bankName'] = $this->bankName;
        }

        if ($this->accountHolder !== null) {
            $data['accountHolder'] = $this->accountHolder;
        }

        if ($this->balance !== null) {
            $data['balance'] = $this->balance;
        }

        if ($this->externalReference !== null) {
            $data['externalReference'] = $this->externalReference;
        }

        if ($this->iban !== null) {
            $data['iban'] = $this->iban;
        }

        if ($this->bic !== null) {
            $data['bic'] = $this->bic;
        }

        if ($this->provider !== null) {
            $data['provider'] = $this->provider->value;
        }

        if ($this->creditCardNumber !== null) {
            $data['creditCardNumber'] = $this->creditCardNumber;
        }

        return $data;
    }

    // Getter für alle Eigenschaften

    /**
     * Gibt die eindeutige ID des Finanzkontos zurück
     */
    public function getFinancialAccountId(): string
    {
        return $this->financialAccountId;
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
     * Gibt die Versionsnummer zurück
     */
    public function getLockVersion(): ?int
    {
        return $this->lockVersion;
    }

    /**
     * Gibt zurück, ob das Konto deaktiviert ist
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated;
    }

    /**
     * Gibt den Status zurück
     */
    public function getState(): ?object
    {
        return $this->state;
    }

    /**
     * Gibt das Datum der ersten Synchronisation zurück
     */
    public function getInitialSyncDate(): ?string
    {
        return $this->initialSyncDate;
    }

    /**
     * Gibt den Kontentyp zurück
     */
    public function getType(): FinancialAccountType
    {
        return $this->type;
    }

    /**
     * Gibt das Kontensystem zurück
     */
    public function getAccountSystem(): AccountSystem
    {
        return $this->accountSystem;
    }

    /**
     * Gibt den Namen des Kontos zurück
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gibt den Namen der Bank zurück
     */
    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    /**
     * Gibt den Kontoinhaber zurück
     */
    public function getAccountHolder(): ?string
    {
        return $this->accountHolder;
    }

    /**
     * Gibt den Kontostand zurück
     */
    public function getBalance(): ?float
    {
        return $this->balance;
    }

    /**
     * Gibt die externe Referenz zurück
     */
    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    /**
     * Gibt die IBAN zurück
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * Gibt die BIC zurück
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * Gibt den Kreditkartenanbieter zurück
     */
    public function getProvider(): ?CreditCardProvider
    {
        return $this->provider;
    }

    /**
     * Gibt die Kreditkartennummer zurück
     */
    public function getCreditCardNumber(): ?string
    {
        return $this->creditCardNumber;
    }

    // Setter für veränderbare Eigenschaften

    /**
     * Setzt den Typ des Finanzkontos
     *
     * @return $this
     */
    public function setType(FinancialAccountType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Setzt den Namen des Finanzkontos
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Setzt den Namen der Bank
     *
     * @return $this
     */
    public function setBankName(?string $bankName): self
    {
        $this->bankName = $bankName;

        return $this;
    }

    /**
     * Setzt den Kontoinhaber
     *
     * @return $this
     */
    public function setAccountHolder(?string $accountHolder): self
    {
        $this->accountHolder = $accountHolder;

        return $this;
    }

    /**
     * Setzt den Kontostand
     *
     * @return $this
     */
    public function setBalance(?float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Setzt die externe Referenz
     *
     * @return $this
     */
    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    /**
     * Setzt die IBAN
     *
     * @return $this
     */
    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Setzt die BIC
     *
     * @return $this
     */
    public function setBic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Setzt den Kreditkartenanbieter
     *
     * @return $this
     */
    public function setProvider(?CreditCardProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Setzt die Kreditkartennummer
     *
     * @return $this
     */
    public function setCreditCardNumber(?string $creditCardNumber): self
    {
        $this->creditCardNumber = $creditCardNumber;

        return $this;
    }
}
