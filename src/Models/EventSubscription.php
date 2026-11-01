<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class EventSubscription implements \JsonSerializable
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $organizationId,
        public readonly string $createdDate,
        public readonly string $eventType,
        public readonly string $callbackUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: $data['subscriptionId'],
            organizationId: $data['organizationId'],
            createdDate: $data['createdDate'],
            eventType: $data['eventType'],
            callbackUrl: $data['callbackUrl'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'subscriptionId' => $this->subscriptionId,
            'organizationId' => $this->organizationId,
            'createdDate' => $this->createdDate,
            'eventType' => $this->eventType,
            'callbackUrl' => $this->callbackUrl,
        ], static fn ($value) => $value !== null);
    }
}
