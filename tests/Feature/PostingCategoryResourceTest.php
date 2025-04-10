<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Pirabyte\LaravelLexwareOffice\Models\PostingCategory;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class PostingCategoryResourceTest extends TestCase
{

    public function test_it_can_parse_posting_category_response()
    {
        $fixtureFile = __DIR__ . '/../Fixtures/posting-categories/1_parse_posting_category_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        foreach($fixtureData as $fixtureCategory) {
            $postingCategory = PostingCategory::fromArray($fixtureCategory);
            $this->validate_posting_category($postingCategory, $fixtureCategory);
        }
    }

    private function validate_posting_category(PostingCategory $postingCategory, array $fixtureData)
    {
        $this->assertInstanceOf(PostingCategory::class, $postingCategory);

        $this->assertEquals($postingCategory->getId(), $fixtureData['id']);
        $this->assertEquals($postingCategory->getName(), $fixtureData['name']);
        $this->assertEquals($postingCategory->getType(), $fixtureData['type']);
        $this->assertEquals($postingCategory->getContactRequired(), $fixtureData['contactRequired']);
        $this->assertEquals($postingCategory->getSplitAllowed(), $fixtureData['splitAllowed']);
        $this->assertEquals($postingCategory->getGroupName(), $fixtureData['groupName']);

    }
}