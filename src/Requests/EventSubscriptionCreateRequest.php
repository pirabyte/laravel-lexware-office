<?php

namespace Pirabyte\LaravelLexwareOffice\Requests;

class EventSubscriptionCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $eventType,
        public readonly string $callbackUrl,
    ) {}

    public function jsonSerialize(): array
    {
        return ['eventType' => $this->eventType, 'callbackUrl' => $this->callbackUrl];
    }
}
