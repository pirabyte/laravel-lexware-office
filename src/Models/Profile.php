<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Profile implements \JsonSerializable
{
    private string $organizationId;

    private string $companyName;

    private array $created;

    private string $connectionId;

    private array $features;

    private array $businessFeatures;

    private string $subscriptionStatus;

    private string $taxType = 'net';

    private bool $smallBusiness = false;

    public function jsonSerialize(): array
    {
        return [
            'organizationId' => $this->organizationId,
            'companyName' => $this->companyName,
            'created' => $this->created,
            'connectionId' => $this->connectionId,
            'features' => $this->features,
            'businessFeatures' => $this->businessFeatures,
            'subscriptionStatus' => $this->subscriptionStatus,
            'taxType' => $this->taxType,
            'smallBusiness' => $this->smallBusiness,
        ];
    }

    public static function fromArray(array $data): self
    {
        $profile = new self();

        if(isset($data["organizationId"])) {
            $profile->organizationId = $data["organizationId"];
        }

        if(isset($data["companyName"])) {
            $profile->companyName = $data["companyName"];
        }

        if(isset($data["created"])) {
            $profile->created = $data["created"];
        }

        if(isset($data["connectionId"])) {
            $profile->connectionId = $data["connectionId"];
        }

        if(isset($data["features"])) {
            $profile->features = $data["features"];
        }

        if(isset($data["businessFeatures"])) {
            $profile->businessFeatures = $data["businessFeatures"];
        }

        if(isset($data["subscriptionStatus"])) {
            $profile->subscriptionStatus = $data["subscriptionStatus"];
        }

        if(isset($data["taxType"])) {
            $profile->taxType = $data["taxType"];
        }

        if(isset($data["smallBusiness"])) {
            $profile->smallBusiness = $data["smallBusiness"];
        }

        return $profile;
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getCreated(): array
    {
        return $this->created;
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getBusinessFeatures(): array
    {
        return $this->businessFeatures;
    }

    public function getSubscriptionStatus(): string
    {
        return $this->subscriptionStatus;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function isSmallBusiness(): bool
    {
        return $this->smallBusiness;
    }
}