<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Company implements \JsonSerializable
{
    private string $name;
    private ?string $taxNumber = null;
    private ?string $vatRegistrationId = null;
    private bool $allowTaxFreeInvoices = false;
    private array $contactPersons = [];

    // Getters und Setters

    public static function fromArray(array $data): self
    {
        $company = new self();

        if (isset($data['name'])) {
            $company->setName($data['name']);
        }

        if (isset($data['taxNumber'])) {
            $company->setTaxNumber($data['taxNumber']);
        }

        if (isset($data['vatRegistrationId'])) {
            $company->setVatRegistrationId($data['vatRegistrationId']);
        }

        if (isset($data['allowTaxFreeInvoices'])) {
            $company->setAllowTaxFreeInvoices($data['allowTaxFreeInvoices']);
        }

        if (isset($data['contactPersons'])) {
            $company->setContactPersons($data['contactPersons']);
        }

        return $company;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name
        ];

        if ($this->taxNumber) {
            $data['taxNumber'] = $this->taxNumber;
        }

        if ($this->vatRegistrationId) {
            $data['vatRegistrationId'] = $this->vatRegistrationId;
        }

        if ($this->allowTaxFreeInvoices) {
            $data['allowTaxFreeInvoices'] = $this->allowTaxFreeInvoices;
        }

        if (!empty($this->contactPersons)) {
            $data['contactPersons'] = $this->contactPersons;
        }

        return $data;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setContactPersons(array $contactPersons): self
    {
        $this->contactPersons = $contactPersons;
        return $this;
    }

    public function setAllowTaxFreeInvoices(bool $allowTaxFreeInvoices): self
    {
        $this->allowTaxFreeInvoices = $allowTaxFreeInvoices;
        return $this;
    }

    public function setTaxNumber(?string $taxNumber): self
    {
        $this->taxNumber = $taxNumber;
        return $this;
    }

    public function setVatRegistrationId(?string $vatRegistrationId): self
    {
        $this->vatRegistrationId = $vatRegistrationId;
        return $this;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }
    
    public function getVatRegistrationId(): ?string
    {
        return $this->vatRegistrationId;
    }
    
    public function getAllowTaxFreeInvoices(): bool
    {
        return $this->allowTaxFreeInvoices;
    }
    
    public function getContactPersons(): array
    {
        return $this->contactPersons;
    }
}