<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

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
     * @return array
     * @throws LexwareOfficeApiException
     */
    public function get(): array
    {
        $response = $this->client->get("posting-categories");
        $categories = [];
        foreach($response as $entry)
        {
            $categories[] = PostingCategory::fromArray($entry);
        }
        return $categories;
    }
}