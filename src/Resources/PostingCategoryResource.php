<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Mappers\PostingCategories\PostingCategoryMapper;
use Pirabyte\LaravelLexwareOffice\Collections\PostingCategories\PostingCategoryCollection;

class PostingCategoryResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    public function get(PostingCategoryType $type = PostingCategoryType::ALL): PostingCategoryCollection
    {
        $response = $this->http->get('posting-categories');
        $all = PostingCategoryMapper::collectionFromJson($response->body);

        if ($type === PostingCategoryType::ALL) {
            return $all;
        }

        $filtered = PostingCategoryCollection::empty();
        foreach ($all as $category) {
            if ($category->type === $type) {
                $filtered = $filtered->with($category);
            }
        }

        return $filtered;
    }
}
