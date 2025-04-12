<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\PostingCategory;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class PostingCategoryResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $fixtureFile = __DIR__.'/../Fixtures/posting-categories/1_parse_posting_category_response.json';
        $fixtureData = file_get_contents($fixtureFile);

        // Mock-Responses für die API-Aufrufe bei Personen-Kontakt
        $personMockResponses = [
            // Response für create
            new Response(200, ['Content-Type' => 'application/json'], $fixtureData),
            new Response(200, ['Content-Type' => 'application/json'], $fixtureData),
        ];

        $mock = new MockHandler($personMockResponses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Client mit Mock-Handler ersetzen
        $instance = app('lexware-office');
        $instance->setClient($client);
    }

    public function test_it_can_get_posting_categories_by_type()
    {
        $categories = LexwareOffice::postingCategories()->get();
        $this->assertCount(4, $categories);

        $categories = LexwareOffice::postingCategories()->get(PostingCategoryType::EXPENSE);
        $this->assertCount(2, $categories);
    }

    public function test_it_can_parse_posting_category_response()
    {
        $fixtureFile = __DIR__.'/../Fixtures/posting-categories/1_parse_posting_category_response.json';
        $fixtureContents = file_get_contents($fixtureFile);
        $fixtureData = json_decode($fixtureContents, true);
        foreach ($fixtureData as $fixtureCategory) {
            $postingCategory = PostingCategory::fromArray($fixtureCategory);
            $this->validate_posting_category($postingCategory, $fixtureCategory);
        }
    }

    private function validate_posting_category(PostingCategory $postingCategory, array $fixtureData): void
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
