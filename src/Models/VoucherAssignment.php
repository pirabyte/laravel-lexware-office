<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class VoucherAssignment
{

    private string $id;

    private string $type;

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * Konvertiert ein Array in eine VoucherAssignment-Instanz
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $assignment = new self();

        if (isset($data['id'])) {
            $assignment->id = $data['id'];
        }

        if (isset($data['type'])) {
            $assignment->type = $data['type'];
        }

        return $assignment;
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}