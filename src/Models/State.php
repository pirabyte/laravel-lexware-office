<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class State
{
    public string $organizationId;
    public string $status;
    public ?string $errorMessage;
    public bool $errorOnSync;
    public ?string $syncStartDate;
    public ?string $syncEndDate;

    public static function fromArray(array $data): self
    {
        $state = new self();
        $state->organizationId = $data['organizationId'];
        $state->status = $data['status'];
        $state->errorMessage = $data['errorMessage'];
        $state->errorOnSync = $data['errorOnSync'];
        $state->syncStartDate = $data['syncStartDate'];
        $state->syncEndDate = $data['syncEndDate'];

        return $state;
    }
}
