<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Contact implements \JsonSerializable
{
    private string $id;
    private ?string $organizationId = null;
    private int $version;
    private array $roles = [];
    private ?Person $person = null;
    private ?Company $company = null;
    private array $addresses = [];
    private array $emailAddresses = [];
    private array $phoneNumbers = [];
    private ?XRechnung $xRechnung = null;
    private ?string $note = null;
    private bool $archived = false;
    private ?string $createdDate = null;
    private ?string $updatedDate = null;

    // Getters und Setters für alle neuen Felder

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function setAddresses(array $addresses): self
    {
        $this->addresses = $addresses;
        return $this;
    }

    public function getEmailAddresses(): array
    {
        return $this->emailAddresses;
    }

    public function setEmailAddresses(array $emailAddresses): self
    {
        $this->emailAddresses = $emailAddresses;
        return $this;
    }

    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    public function setPhoneNumbers(array $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;
        return $this;
    }

    public function getXRechnung(): ?XRechnung
    {
        return $this->xRechnung;
    }

    public function setXRechnung(?XRechnung $xRechnung): self
    {
        $this->xRechnung = $xRechnung;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }

    // Factory-Methode aktualisieren
    public static function fromArray(array $data): self
    {
        $contact = new self();

        if (isset($data['id'])) {
            $contact->setId($data['id']);
        }

        if (isset($data['organizationId'])) {
            $contact->setOrganizationId($data['organizationId']);
        }

        if (isset($data['version'])) {
            $contact->setVersion($data['version']);
        }

        if (isset($data['roles'])) {
            $contact->setRoles($data['roles']);
        }

        if (isset($data['person'])) {
            if ($data['person'] instanceof Person) {
                $contact->setPerson($data['person']);
            } else {
                $contact->setPerson(Person::fromArray($data['person']));
            }
        }

        if (isset($data['company'])) {
            if ($data['company'] instanceof Company) {
                $contact->setCompany($data['company']);
            } else {
                $contact->setCompany(Company::fromArray($data['company']));
            }
        }

        if (isset($data['addresses'])) {
            $contact->setAddresses($data['addresses']);
        }

        if (isset($data['emailAddresses'])) {
            $contact->setEmailAddresses($data['emailAddresses']);
        }

        if (isset($data['phoneNumbers'])) {
            $contact->setPhoneNumbers($data['phoneNumbers']);
        }

        if (isset($data['xRechnung'])) {
            if ($data['xRechnung'] instanceof XRechnung) {
                $contact->setXRechnung($data['xRechnung']);
            } else {
                $contact->setXRechnung(XRechnung::fromArray($data['xRechnung']));
            }
        }

        if (isset($data['note'])) {
            $contact->setNote($data['note']);
        }

        if (isset($data['archived'])) {
            $contact->setArchived($data['archived']);
        }

        if (isset($data['createdDate'])) {
            $contact->setCreatedDate($data['createdDate']);
        }

        if (isset($data['updatedDate'])) {
            $contact->setUpdatedDate($data['updatedDate']);
        }

        return $contact;
    }

    // jsonSerialize aktualisieren
    public function jsonSerialize(): array
    {
        $data = [
            'version' => $this->version,
            'roles' => $this->roles
        ];

        if (isset($this->id)) {
            $data['id'] = $this->id;
        }

        if ($this->person) {
            $data['person'] = $this->person;
        }

        if ($this->company) {
            $data['company'] = $this->company;
        }

        if (!empty($this->addresses)) {
            $data['addresses'] = $this->addresses;
        }

        if (!empty($this->emailAddresses)) {
            $data['emailAddresses'] = $this->emailAddresses;
        }

        if (!empty($this->phoneNumbers)) {
            $data['phoneNumbers'] = $this->phoneNumbers;
        }

        if ($this->xRechnung) {
            $data['xRechnung'] = $this->xRechnung;
        }

        if ($this->note) {
            $data['note'] = $this->note;
        }

        if ($this->archived) {
            $data['archived'] = $this->archived;
        }

        return $data;
    }

    private function setRoles(mixed $roles): void
    {
        $this->roles = $roles;
    }

    private function setUpdatedDate(mixed $updatedDate): void
    {
        $this->updatedDate = $updatedDate;
    }

    private function setCreatedDate(mixed $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    private function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    private function setPerson(Person $person): void
    {
        $this->person = $person;
    }

    private function setVersion(mixed $version): void
    {
        $this->version = $version;
    }

    private function setOrganizationId(mixed $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    private function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
    
    public function getVersion(): int
    {
        return $this->version;
    }
}