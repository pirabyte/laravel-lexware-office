<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PrintLayout implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $default,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            default: $data['default'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'default' => $this->default,
        ], static fn ($value) => $value !== null);
    }
}
