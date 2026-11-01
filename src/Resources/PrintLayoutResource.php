<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\PrintLayout;

class PrintLayoutResource
{
    public function __construct(protected LexwareOffice $client) {}

    /** @return list<PrintLayout> */
    public function all(): array
    {
        return array_map(PrintLayout::fromArray(...), $this->client->get('print-layouts'));
    }
}
