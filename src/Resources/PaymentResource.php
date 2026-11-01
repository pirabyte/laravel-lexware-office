<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Payment;

class PaymentResource
{
    public function __construct(protected LexwareOffice $client) {}

    public function get(string $voucherId): Payment
    {
        return Payment::fromArray($this->client->get('payments/'.rawurlencode($voucherId)));
    }
}
