<?php

namespace Pirabyte\LaravelLexwareOffice\Classes;

class PaginatedResource implements \JsonSerializable
{
    private array $content = [];

    private bool $first = false;

    private bool $last = false;

    private int $totalPages = 0;

    private int $totalElements = 0;

    private int $numberOfElements = 0;

    private int $size = 0;

    private int $number = 0;

    private array $sort = [];

    public function jsonSerialize(): array
    {
        return [
            'content' => $this->content,
            'first' => $this->first,
            'last' => $this->last,
            'totalPages' => $this->totalPages,
            'totalElements' => $this->totalElements,
            'numberOfElements' => $this->numberOfElements,
            'size' => $this->size,
            'number' => $this->number,
            'sort' => $this->sort,
        ];
    }

    /**
     * Erstellt ein PaginatedResource-Objekt aus einem Array
     * ACHTUNG: Content wird hier nicht gesetzt!
     * @param array $data
     * @return PaginatedResource
     */
    public static function fromArray(array $data): self
    {
        $resource = new self();

        if(isset($data["first"])) {
            $resource->first = $data["first"];
        }

        if(isset($data["last"])) {
            $resource->last = $data["last"];
        }

        if(isset($data["totalPages"])) {
            $resource->totalPages = $data["totalPages"];
        }

        if(isset($data["totalElements"])) {
            $resource->totalElements = $data["totalElements"];
        }

        if(isset($data["numberOfElements"])) {
            $resource->numberOfElements = $data["numberOfElements"];
        }

        if(isset($data["size"])) {
            $resource->size = $data["size"];
        }

        if(isset($data["number"])) {
            $resource->number = $data["number"];
        }

        if(isset($data["sort"])) {
            $resource->sort = $data["sort"];
        }

        return $resource;
    }

    public function appendContent(mixed $model): void
    {
        $this->content[] = $model;
    }
}

