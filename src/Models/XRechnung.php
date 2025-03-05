<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class XRechnung implements \JsonSerializable
{
    private ?string $buyerReference = null;
    private ?string $vendorNumberAtCustomer = null;

    // Getters und Setters

    public static function fromArray(array $data): self
    {
        $xRechnung = new self();

        if (isset($data['buyerReference'])) {
            $xRechnung->setBuyerReference($data['buyerReference']);
        }

        if (isset($data['vendorNumberAtCustomer'])) {
            $xRechnung->setVendorNumberAtCustomer($data['vendorNumberAtCustomer']);
        }

        return $xRechnung;
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->buyerReference) {
            $data['buyerReference'] = $this->buyerReference;
        }

        if ($this->vendorNumberAtCustomer) {
            $data['vendorNumberAtCustomer'] = $this->vendorNumberAtCustomer;
        }

        return $data;
    }

    private function setBuyerReference(string|null $buyerReference): void
    {
        $this->buyerReference = $buyerReference;
    }

    private function setVendorNumberAtCustomer(string|null $vendorNumberAtCustomer): void
    {
        $this->vendorNumberAtCustomer = $vendorNumberAtCustomer;
    }
}