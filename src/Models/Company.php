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

    private function setName(string $name): void
    {
        $this->name = $name;
    }

    private function setContactPersons(array $contactPersons): void
    {
        $this->contactPersons = $contactPersons;
    }

    private function setAllowTaxFreeInvoices(bool $allowTaxFreeInvoices): void
    {
        $this->allowTaxFreeInvoices = $allowTaxFreeInvoices;
    }

    private function setTaxNumber(string|null $taxNumber): void
    {
        $this->taxNumber = $taxNumber;
    }

    private function setVatRegistrationId(string|null $vatRegistrationId): void
    {
        $this->vatRegistrationId = $vatRegistrationId;
    }
}