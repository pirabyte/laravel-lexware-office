<?php

namespace Pirabyte\LaravelLexwareOffice\Responses;

class UpdateResponse implements \JsonSerializable
{
    private string $id;

    private string $resourceUri;

    private string $createdDate;

    private string $updatedDate;

    private int $version;

    public static function fromArray(array $data): UpdateResponse
    {
        $response = new UpdateResponse;
        $response->id = $data['id'];
        $response->resourceUri = $data['resourceUri'];
        $response->createdDate = $data['createdDate'];
        $response->updatedDate = $data['updatedDate'];
        $response->version = $data['version'];
        return $response;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'resourceUri' => $this->resourceUri,
            'createdDate' => $this->createdDate,
            'updatedDate' => $this->updatedDate,
            'version' => $this->version,
        ];
    }
}