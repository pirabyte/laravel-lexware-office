<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class OAuth2IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('lexware-office.oauth2.client_id', 'test_client_id');
        Config::set('lexware-office.oauth2.client_secret', 'test_client_secret');
        Config::set('lexware-office.oauth2.redirect_uri', 'https://example.com/callback');
        Config::set('lexware-office.oauth2.scopes', ['profile', 'contacts']);
        Config::set('lexware-office.base_url', 'https://api.lexoffice.de');
        Config::set('lexware-office.oauth2.token_storage', 'cache');

        // Create tokens table for database tests using Schema builder
        if (!DB::getSchemaBuilder()->hasTable('lexware_tokens')) {
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

    public function test_complete_oauth2_authorization_flow()
    {
        $userId = 'test_user_123';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Step 1: Get authorization URL
        $authUrl = $lexware->getOAuth2AuthorizationUrl('custom_state');
        
        $this->assertStringContainsString('https://api.lexoffice.de/oauth2/authorize', $authUrl->getUrl());
        $this->assertStringContainsString('state=custom_state', $authUrl->getUrl());
        $this->assertNotEmpty($authUrl->getCodeVerifier());

        // Step 2: Mock token exchange
        $tokenResponse = [
            'access_token' => 'integration_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'integration_refresh_token',
            'scope' => 'profile contacts'
        ];

        // Store PKCE data in cache
        Cache::put('lexware_pkce_custom_state', $authUrl->getCodeVerifier(), now()->addMinutes(10));

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($tokenResponse)),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        // Step 3: Exchange authorization code for token
        $token = $lexware->exchangeOAuth2CodeForToken('auth_code_123', 'custom_state');

        $this->assertEquals('integration_access_token', $token->getAccessToken());
        $this->assertEquals('integration_refresh_token', $token->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $token->getScopes());

        // Step 4: Verify token is stored and can be retrieved
        $storedToken = $lexware->getValidOAuth2Token();
        $this->assertNotNull($storedToken);
        $this->assertEquals('integration_access_token', $storedToken->getAccessToken());
    }

    public function test_automatic_token_refresh_on_api_call()
    {
        $userId = 'test_user_456';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store an expired token
        $expiredToken = new LexwareAccessToken(
            'expired_access_token',
            'Bearer',
            3600,
            'valid_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H')) // 2 hours ago
        );

        $oauth2Service = $lexware->getOAuth2Service();
        $oauth2Service->getTokenStorage()->storeToken($expiredToken);

        // Mock responses: first 401 (expired), then token refresh, then successful API call
        $refreshResponse = [
            'access_token' => 'new_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_token',
            'scope' => 'profile'
        ];

        $profileResponse = [
            'organizationId' => 'org_123',
            'companyName' => 'Test Company',
            'created' => [
                'userId' => 'user_123',
                'userName' => 'Test User',
                'userEmail' => 'test@example.com',
                'date' => '2023-01-01T00:00:00+01:00'
            ],
            'connectionId' => 'conn_123'
        ];

        $mock = new MockHandler([
            // Token refresh call (made by ensureValidToken since token is expired)
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
            // Profile API call with fresh token
            new Response(200, ['Content-Type' => 'application/json'], json_encode($profileResponse)),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        // Make API call that should trigger automatic token refresh
        $profile = $lexware->profile()->get();

        $this->assertEquals('org_123', $profile->getOrganizationId());
        $this->assertEquals('Test Company', $profile->getCompanyName());

        // Verify token was refreshed and stored
        $newToken = $oauth2Service->getTokenStorage()->getToken();
        $this->assertEquals('new_access_token', $newToken->getAccessToken());
        $this->assertEquals('new_refresh_token', $newToken->getRefreshToken());
    }

    public function test_oauth2_with_database_token_storage()
    {
        Config::set('lexware-office.oauth2.token_storage', 'database');
        
        $userId = 'db_user_789';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store token in database
        $token = new LexwareAccessToken(
            'db_access_token',
            'Bearer',
            3600,
            'db_refresh_token',
            ['profile', 'contacts']
        );

        $oauth2Service = $lexware->getOAuth2Service();
        $oauth2Service->getTokenStorage()->storeToken($token);

        // Verify token is stored in database
        $dbRecord = DB::table('lexware_tokens')->where('user_id', $userId)->first();
        $this->assertNotNull($dbRecord);
        $this->assertEquals('db_access_token', $dbRecord->access_token);
        $this->assertEquals('db_refresh_token', $dbRecord->refresh_token);

        // Verify token can be retrieved
        $retrievedToken = $oauth2Service->getTokenStorage()->getToken();
        $this->assertEquals('db_access_token', $retrievedToken->getAccessToken());
        $this->assertEquals('db_refresh_token', $retrievedToken->getRefreshToken());
    }

    public function test_oauth2_token_revocation_clears_storage()
    {
        $userId = 'revoke_user_101';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store a token
        $token = new LexwareAccessToken('revoke_token', 'Bearer', 3600, 'refresh_token');
        $oauth2Service = $lexware->getOAuth2Service();
        $oauth2Service->getTokenStorage()->storeToken($token);

        $this->assertNotNull($oauth2Service->getTokenStorage()->getToken());

        // Mock successful revocation
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        // Revoke token
        $result = $lexware->revokeOAuth2Token();
        $this->assertTrue($result);

        // Verify token is cleared from storage
        $this->assertNull($oauth2Service->getTokenStorage()->getToken());
    }

    public function test_oauth2_error_handling_during_token_exchange()
    {
        $userId = 'error_user_202';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store PKCE data
        Cache::put('lexware_pkce_error_state', 'verifier_123', now()->addMinutes(10));

        // Mock error response
        $request = new Request('POST', 'oauth2/token');
        $exception = new RequestException(
            'Bad request',
            $request,
            new Response(400, [], '{"error": "invalid_grant", "error_description": "Authorization code is invalid"}')
        );

        $mock = new MockHandler([$exception]);
        $this->setMockHttpClient($lexware, $mock);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Failed to exchange authorization code');

        $lexware->exchangeOAuth2CodeForToken('invalid_code', 'error_state');
    }

    public function test_oauth2_handles_refresh_token_expiry()
    {
        $userId = 'expired_refresh_user_303';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store token with expired refresh token scenario
        $expiredToken = new LexwareAccessToken(
            'expired_access_token',
            'Bearer',
            3600,
            'expired_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        $oauth2Service = $lexware->getOAuth2Service();
        $oauth2Service->getTokenStorage()->storeToken($expiredToken);

        // Mock responses: API call fails, token refresh fails
        $request = new Request('POST', 'oauth2/token');
        $refreshException = new RequestException(
            'Invalid refresh token',
            $request,
            new Response(400, [], '{"error": "invalid_grant"}')
        );

        $mock = new MockHandler([
            new Response(401, [], '{"error": "invalid_token"}'), // API call fails
            $refreshException, // Refresh fails
        ]);

        $this->setMockHttpClient($lexware, $mock);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Failed to refresh token');

        // This should fail when trying to refresh the expired token
        $lexware->profile()->get();
    }

    public function test_multiple_users_have_isolated_oauth2_tokens()
    {
        $user1 = 'isolated_user_1';
        $user2 = 'isolated_user_2';
        
        $lexware1 = LexwareOfficeFactory::forUser($user1);
        $lexware2 = LexwareOfficeFactory::forUser($user2);

        // Store different tokens for each user
        $token1 = new LexwareAccessToken('token_user_1', 'Bearer', 3600, 'refresh_1');
        $token2 = new LexwareAccessToken('token_user_2', 'Bearer', 3600, 'refresh_2');

        $lexware1->getOAuth2Service()->getTokenStorage()->storeToken($token1);
        $lexware2->getOAuth2Service()->getTokenStorage()->storeToken($token2);

        // Verify each user gets their own token
        $retrieved1 = $lexware1->getOAuth2Service()->getTokenStorage()->getToken();
        $retrieved2 = $lexware2->getOAuth2Service()->getTokenStorage()->getToken();

        $this->assertEquals('token_user_1', $retrieved1->getAccessToken());
        $this->assertEquals('token_user_2', $retrieved2->getAccessToken());
        $this->assertNotEquals($retrieved1->getAccessToken(), $retrieved2->getAccessToken());
    }

    public function test_oauth2_scope_validation()
    {
        $userId = 'scope_user_404';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store token with limited scopes
        $token = new LexwareAccessToken(
            'limited_scope_token',
            'Bearer',
            3600,
            'refresh_token',
            ['profile'] // Missing 'contacts' scope
        );

        $oauth2Service = $lexware->getOAuth2Service();
        $oauth2Service->getTokenStorage()->storeToken($token);

        $storedToken = $oauth2Service->getTokenStorage()->getToken();
        
        $this->assertTrue($storedToken->hasScope('profile'));
        $this->assertFalse($storedToken->hasScope('contacts'));
        $this->assertFalse($storedToken->hasScope('invoices'));
        
        $this->assertTrue($storedToken->hasScopes(['profile']));
        $this->assertFalse($storedToken->hasScopes(['profile', 'contacts']));
    }

    public function test_oauth2_token_persistence_across_requests()
    {
        $userId = 'persistent_user_505';
        
        // First request - store token
        $lexware1 = LexwareOfficeFactory::forUser($userId);
        $token = new LexwareAccessToken('persistent_token', 'Bearer', 3600, 'refresh_token');
        $lexware1->getOAuth2Service()->getTokenStorage()->storeToken($token);

        // Second request - new instance should retrieve same token
        $lexware2 = LexwareOfficeFactory::forUser($userId);
        $retrievedToken = $lexware2->getOAuth2Service()->getTokenStorage()->getToken();

        $this->assertNotNull($retrievedToken);
        $this->assertEquals('persistent_token', $retrievedToken->getAccessToken());
    }

    private function setMockHttpClient(LexwareOffice $lexware, MockHandler $mock): void
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Set HTTP client for main LexwareOffice instance
        $reflection = new \ReflectionClass($lexware);
        
        // Update both client properties
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($lexware, $client);
        
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($lexware, $client);

        // Set HTTP client for OAuth2 service
        $oauth2Service = $lexware->getOAuth2Service();
        if ($oauth2Service) {
            $oauth2Reflection = new \ReflectionClass($oauth2Service);
            $oauth2HttpClientProperty = $oauth2Reflection->getProperty('httpClient');
            $oauth2HttpClientProperty->setValue($oauth2Service, $client);
        }
    }
}