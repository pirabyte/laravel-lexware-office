<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\PostingCategoryType;
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Dto\PostingCategories\PostingCategory;
use Pirabyte\LaravelLexwareOffice\Mappers\PostingCategories\PostingCategoryMapper;
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

        $categories = PostingCategoryMapper::collectionFromJson($fixtureContents);
        $this->assertCount(count($fixtureData), $categories);

        foreach ($fixtureData as $index => $fixtureCategory) {
            /** @var PostingCategory|null $postingCategory */
            $postingCategory = $categories->get($index);
            $this->assertNotNull($postingCategory);

            $this->assertEquals($fixtureCategory['id'], $postingCategory->id);
            $this->assertEquals($fixtureCategory['name'], $postingCategory->name);
            $this->assertEquals($fixtureCategory['type'], $postingCategory->type->value);
            $this->assertEquals($fixtureCategory['contactRequired'], $postingCategory->contactRequired);
            $this->assertEquals($fixtureCategory['splitAllowed'], $postingCategory->splitAllowed);
            $this->assertEquals($fixtureCategory['groupName'], $postingCategory->groupName);
        }
    }
}
