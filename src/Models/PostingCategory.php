<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class PostingCategory implements \JsonSerializable
{

    private string $id;

    private string $name;

    private string $type;

    private bool $contactRequired = false;

    private bool $splitAllowed = false;

    private string $groupName;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'contactRequired' => $this->contactRequired,
            'splitAllowed' => $this->splitAllowed,
            'groupName' => $this->groupName
        ];
    }

    public static function fromArray(array $data): self
    {
        $postingCategory = new self();

        if(isset($data['id'])) {
            $postingCategory->id = $data['id'];
        }

        if(isset($data['name'])) {
            $postingCategory->name = $data['name'];
        }

        if(isset($data['type'])) {
            $postingCategory->type = $data['type'];
        }

        if(isset($data['contactRequired'])) {
            $postingCategory->contactRequired = $data['contactRequired'];
        }

        if(isset($data['splitAllowed'])) {
            $postingCategory->splitAllowed = $data['splitAllowed'];
        }

        if(isset($data['groupName'])) {
            $postingCategory->groupName = $data['groupName'];
        }

        return $postingCategory;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContactRequired(): bool
    {
        return $this->contactRequired;
    }

    public function getSplitAllowed(): bool
    {
        return $this->splitAllowed;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }
}