<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class TransactionAssignmentHint implements \JsonSerializable
{
    private string $voucherId;
    private string $externalReference;

    public static function fromArray(array $data): self
    {
        $hint = new self();

        if (isset($data['voucherId'])) {
            $hint->setVoucherId($data['voucherId']);
        }

        if (isset($data['externalReference'])) {
            $hint->setExternalReference($data['externalReference']);
        }

        return $hint;
    }

    public function jsonSerialize(): array
    {
        return [
            'voucherId' => $this->voucherId,
            'externalReference' => $this->externalReference
        ];
    }

    public function getVoucherId(): string
    {
        return $this->voucherId;
    }

    private function setVoucherId(string $voucherId): void
    {
        $this->voucherId = $voucherId;
    }

    public function getExternalReference(): string
    {
        return $this->externalReference;
    }

    private function setExternalReference(string $externalReference): void
    {
        $this->externalReference = $externalReference;
    }
}