<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\EventSubscription;
use Pirabyte\LaravelLexwareOffice\Requests\EventSubscriptionCreateRequest;
use Pirabyte\LaravelLexwareOffice\Responses\UpdateResponse;

class EventSubscriptionResource
{
    public function __construct(protected LexwareOffice $client) {}

    public function create(EventSubscriptionCreateRequest $request): UpdateResponse
    {
        return UpdateResponse::fromArray($this->client->post('event-subscriptions', $request->jsonSerialize()));
    }

    /** @return list<EventSubscription> */
    public function all(): array
    {
        return array_map(EventSubscription::fromArray(...), $this->client->get('event-subscriptions')['content']);
    }

    public function get(string $id): EventSubscription
    {
        return EventSubscription::fromArray($this->client->get('event-subscriptions/'.rawurlencode($id)));
    }

    public function delete(string $id): void
    {
        $this->client->delete('event-subscriptions/'.rawurlencode($id));
    }
}
