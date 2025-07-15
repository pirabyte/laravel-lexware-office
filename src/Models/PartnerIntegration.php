<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PartnerIntegration implements \JsonSerializable
{
    private array $data = [];

    public static function fromArray(array $data): self
    {
        $integration = new self();
        $integration->data = $data;

        return $integration;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }
}
