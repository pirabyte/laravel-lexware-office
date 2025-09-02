<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\DatabaseTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class TokenStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create the tokens table for database storage tests using Schema builder
        if (! DB::getSchemaBuilder()->hasTable('lexware_tokens')) {
            DB::getSchemaBuilder()->create('lexware_tokens', function ($table) {
                $table->id();
                $table->string('user_id')->unique();
                $table->text('access_token');
                $table->string('token_type', 50)->default('Bearer');
                $table->integer('expires_in');
                $table->text('refresh_token')->nullable();
                $table->text('scopes')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Cache::flush();
        DB::getSchemaBuilder()->dropIfExists('lexware_tokens');
        parent::tearDown();
    }

    // Cache Token Storage Tests

    public function test_cache_storage_stores_and_retrieves_token()
    {
        $storage = new CacheTokenStorage('test_key');
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts']
        );

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('access_token_123', $retrievedToken->getAccessToken());
        $this->assertEquals('Bearer', $retrievedToken->getTokenType());
        $this->assertEquals(3600, $retrievedToken->getExpiresIn());
        $this->assertEquals('refresh_token_456', $retrievedToken->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $retrievedToken->getScopes());
    }

    public function test_cache_storage_returns_null_when_no_token_stored()
    {
        $storage = new CacheTokenStorage('nonexistent_key');

        $this->assertNull($storage->getToken());
    }

    public function test_cache_storage_clears_token()
    {
        $storage = new CacheTokenStorage('test_key');
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $storage->storeToken($token);
        $this->assertNotNull($storage->getToken());

        $storage->clearToken();
        $this->assertNull($storage->getToken());
    }

    public function test_cache_storage_overwrites_existing_token()
    {
        $storage = new CacheTokenStorage('test_key');

        $token1 = new LexwareAccessToken('access_token_1', 'Bearer', 3600);
        $token2 = new LexwareAccessToken('access_token_2', 'Bearer', 3600);

        $storage->storeToken($token1);
        $storage->storeToken($token2);

        $retrievedToken = $storage->getToken();
        $this->assertEquals('access_token_2', $retrievedToken->getAccessToken());
    }

    public function test_cache_storage_handles_token_with_minimal_data()
    {
        $storage = new CacheTokenStorage('test_key');
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('access_token_123', $retrievedToken->getAccessToken());
        $this->assertNull($retrievedToken->getRefreshToken());
        $this->assertEquals([], $retrievedToken->getScopes());
    }

    // Database Token Storage Tests

    public function test_database_storage_stores_and_retrieves_token()
    {
        $storage = new DatabaseTokenStorage(123);
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts']
        );

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('access_token_123', $retrievedToken->getAccessToken());
        $this->assertEquals('Bearer', $retrievedToken->getTokenType());
        $this->assertEquals(3600, $retrievedToken->getExpiresIn());
        $this->assertEquals('refresh_token_456', $retrievedToken->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $retrievedToken->getScopes());
    }

    public function test_database_storage_returns_null_when_no_token_stored()
    {
        $storage = new DatabaseTokenStorage(999);

        $this->assertNull($storage->getToken());
    }

    public function test_database_storage_clears_token()
    {
        $storage = new DatabaseTokenStorage(124);
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $storage->storeToken($token);
        $this->assertNotNull($storage->getToken());

        $storage->clearToken();
        $this->assertNull($storage->getToken());
    }

    public function test_database_storage_updates_existing_token()
    {
        $storage = new DatabaseTokenStorage(125);

        $token1 = new LexwareAccessToken('access_token_1', 'Bearer', 3600, 'refresh_1');
        $token2 = new LexwareAccessToken('access_token_2', 'Bearer', 7200, 'refresh_2');

        $storage->storeToken($token1);
        $storage->storeToken($token2);

        $retrievedToken = $storage->getToken();
        $this->assertEquals('access_token_2', $retrievedToken->getAccessToken());
        $this->assertEquals(7200, $retrievedToken->getExpiresIn());
        $this->assertEquals('refresh_2', $retrievedToken->getRefreshToken());

        // Verify only one record exists for this user
        $count = DB::table('lexware_tokens')->where('user_id', 125)->count();
        $this->assertEquals(1, $count);
    }

    public function test_database_storage_handles_string_user_ids()
    {
        $storage = new DatabaseTokenStorage('user_abc_123');
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('access_token_123', $retrievedToken->getAccessToken());
    }

    public function test_database_storage_isolates_tokens_by_user()
    {
        $storage1 = new DatabaseTokenStorage(126);
        $storage2 = new DatabaseTokenStorage(127);

        $token1 = new LexwareAccessToken('access_token_user_126', 'Bearer', 3600);
        $token2 = new LexwareAccessToken('access_token_user_127', 'Bearer', 3600);

        $storage1->storeToken($token1);
        $storage2->storeToken($token2);

        $retrievedToken1 = $storage1->getToken();
        $retrievedToken2 = $storage2->getToken();

        $this->assertEquals('access_token_user_126', $retrievedToken1->getAccessToken());
        $this->assertEquals('access_token_user_127', $retrievedToken2->getAccessToken());
    }

    public function test_database_storage_handles_token_with_minimal_data()
    {
        $storage = new DatabaseTokenStorage(128);
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('access_token_123', $retrievedToken->getAccessToken());
        $this->assertNull($retrievedToken->getRefreshToken());
        $this->assertEquals([], $retrievedToken->getScopes());
    }

    public function test_database_storage_handles_empty_scopes()
    {
        $storage = new DatabaseTokenStorage(129);
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            []
        );

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertEquals([], $retrievedToken->getScopes());
    }

    public function test_database_storage_handles_null_scopes()
    {
        $storage = new DatabaseTokenStorage(130);

        // Manually insert record with NULL scopes
        DB::table('lexware_tokens')->insert([
            'user_id' => 130,
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh_token_456',
            'scopes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retrievedToken = $storage->getToken();
        $this->assertEquals([], $retrievedToken->getScopes());
    }

    public function test_database_storage_preserves_token_creation_time()
    {
        $storage = new DatabaseTokenStorage(131);
        $createdAt = new \DateTime('2023-01-01 12:00:00');

        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile'],
            $createdAt
        );

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertEquals(
            $createdAt->format('Y-m-d H:i:s'),
            $retrievedToken->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function test_cache_storage_preserves_token_creation_time()
    {
        $storage = new CacheTokenStorage('test_key');
        $createdAt = new \DateTime('2023-01-01 12:00:00');

        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile'],
            $createdAt
        );

        $storage->storeToken($token);
        $retrievedToken = $storage->getToken();

        $this->assertEquals(
            $createdAt->format('Y-m-d H:i:s'),
            $retrievedToken->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function test_database_storage_updates_timestamps()
    {
        $storage = new DatabaseTokenStorage(132);
        $token = new LexwareAccessToken('access_token_123', 'Bearer', 3600);

        $beforeStore = now()->subSecond(); // Give 1 second buffer before
        $storage->storeToken($token);
        $afterStore = now()->addSecond(); // Give 1 second buffer after

        // Check that updated_at timestamp is within expected range
        $record = DB::table('lexware_tokens')->where('user_id', 132)->first();

        $this->assertNotNull($record, 'Record should exist in database');
        $this->assertNotNull($record->updated_at, 'updated_at should not be null');

        $updatedAt = \Carbon\Carbon::parse($record->updated_at);

        $this->assertTrue($updatedAt->between($beforeStore, $afterStore));
    }

    public function test_cache_and_database_storage_are_interchangeable()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts']
        );

        // Store with cache
        $cacheStorage = new CacheTokenStorage('test_key');
        $cacheStorage->storeToken($token);
        $cacheRetrieved = $cacheStorage->getToken();

        // Store with database
        $dbStorage = new DatabaseTokenStorage(133);
        $dbStorage->storeToken($token);
        $dbRetrieved = $dbStorage->getToken();

        // Both should have same token data
        $this->assertEquals($cacheRetrieved->getAccessToken(), $dbRetrieved->getAccessToken());
        $this->assertEquals($cacheRetrieved->getTokenType(), $dbRetrieved->getTokenType());
        $this->assertEquals($cacheRetrieved->getExpiresIn(), $dbRetrieved->getExpiresIn());
        $this->assertEquals($cacheRetrieved->getRefreshToken(), $dbRetrieved->getRefreshToken());
        $this->assertEquals($cacheRetrieved->getScopes(), $dbRetrieved->getScopes());
    }
}
