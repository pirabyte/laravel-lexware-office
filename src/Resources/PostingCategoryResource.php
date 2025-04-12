<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\PostingCategory;

class PostingCategoryResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Ruft alle Posting Categories ab
     *
     * @throws LexwareOfficeApiException
     */
    public function get(PostingCategoryType $type = PostingCategoryType::ALL): array
    {
        $response = $this->client->get('posting-categories');
        $categories = [];

        foreach ($response as $entry) {
            if ($type === PostingCategoryType::ALL || $type->value === $entry['type']) {
                // Nur wenn die Bedingung zutrifft, wird die Kategorie hinzugefügt.
                $categories[] = PostingCategory::fromArray($entry);
            }
        }

        return $categories;
    }
}
