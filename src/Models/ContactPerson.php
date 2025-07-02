<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class ContactPerson extends Person implements \JsonSerializable
{
    private bool $primary = false;

    private ?string $emailAddress = null;

    private ?string $phoneNumber = null;

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function getPrimary(): bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public static function fromArray(array $data): self
    {
        $contactPerson = new self();

        if (isset($data['salutation'])) {
            $contactPerson->setSalutation($data['salutation']);
        }

        if (isset($data['firstName'])) {
            $contactPerson->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $contactPerson->setLastName($data['lastName']);
        }

        if (isset($data['primary'])) {
            $contactPerson->setPrimary($data['primary']);
        }

        if (isset($data['emailAddress'])) {
            $contactPerson->setEmailAddress($data['emailAddress']);
        }

        if (isset($data['phoneNumber'])) {
            $contactPerson->setPhoneNumber($data['phoneNumber']);
        }

        return $contactPerson;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        $data['primary'] = $this->primary;

        if ($this->emailAddress) {
            $data['emailAddress'] = $this->emailAddress;
        }

        if ($this->phoneNumber) {
            $data['phoneNumber'] = $this->phoneNumber;
        }

        return $data;
    }
}
