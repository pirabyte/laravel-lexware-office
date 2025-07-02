<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Address implements \JsonSerializable
{
    public ?string $supplement = null;
    public string $street;
    public string $zip;
    public string $city;
    public string $countryCode;

    public static function fromArray(array $data): self
    {
        $address = new self();
        $address->street = $data['street'];
        $address->zip = $data['zip'];
        $address->city = $data['city'];
        $address->countryCode = $data['countryCode'];
        if (isset($data['supplement'])) {
            $address->supplement = $data['supplement'];
        }

        return $address;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
        ];

        if ($this->supplement) {
            $data['supplement'] = $this->supplement;
        }

        return $data;
    }

    public function getSupplement(): ?string
    {
        return $this->supplement;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }
}
