<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Person implements \JsonSerializable
{
    private ?string $salutation = null;

    private ?string $firstName = null;

    private ?string $lastName = null;

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(?string $salutation): self
    {
        $this->salutation = $salutation;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public static function fromArray(array $data): self
    {
        $person = new self;

        if (isset($data['salutation'])) {
            $person->setSalutation($data['salutation']);
        }

        if (isset($data['firstName'])) {
            $person->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $person->setLastName($data['lastName']);
        }

        return $person;
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->salutation) {
            $data['salutation'] = $this->salutation;
        }

        if ($this->firstName) {
            $data['firstName'] = $this->firstName;
        }

        if ($this->lastName) {
            $data['lastName'] = $this->lastName;
        }

        return $data;
    }
}
