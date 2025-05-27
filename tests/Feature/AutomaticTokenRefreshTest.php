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
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class AutomaticTokenRefreshTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_refreshes_expired_token_on_profile_api_call()
    {
        $userId = 'refresh_user_1';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Store expired token
        $expiredToken = new LexwareAccessToken(
            'expired_token',
            'Bearer',
            3600,
            'valid_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($expiredToken);

        $refreshResponse = [
            'access_token' => 'refreshed_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_token',
            'scope' => 'profile'
        ];

        $profileResponse = [
            'organizationId' => 'refresh_org_123',
            'companyName' => 'Refreshed Company',
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

        $profile = $lexware->profile()->get();

        $this->assertEquals('refresh_org_123', $profile->getOrganizationId());
        
        // Verify token was refreshed
        $newToken = $lexware->getOAuth2Service()->getTokenStorage()->getToken();
        $this->assertEquals('refreshed_access_token', $newToken->getAccessToken());
        $this->assertEquals('new_refresh_token', $newToken->getRefreshToken());
    }

    public function test_refreshes_expired_token_on_contacts_api_call()
    {
        $userId = 'refresh_user_2';
        $lexware = LexwareOfficeFactory::forUser($userId);

        $expiredToken = new LexwareAccessToken(
            'expired_token',
            'Bearer',
            3600,
            'valid_refresh_token',
            ['contacts'],
            (new \DateTime())->sub(new \DateInterval('PT90M')) // 90 minutes ago
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($expiredToken);

        $refreshResponse = [
            'access_token' => 'refreshed_for_contacts',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_for_contacts',
            'scope' => 'contacts'
        ];

        $contactsResponse = [
            'content' => [
                [
                    'id' => 'contact_123',
                    'roles' => ['customer'],
                    'company' => ['name' => 'Test Customer']
                ]
            ],
            'totalPages' => 1,
            'totalElements' => 1
        ];

        $mock = new MockHandler([
            // Token refresh call (made by ensureValidToken since token is expired)
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
            // Contacts API call with fresh token
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsResponse)),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        $contacts = $lexware->contacts()->filter();

        $this->assertCount(1, $contacts->getContent());
        $this->assertEquals('contact_123', $contacts->getContent()[0]->getId());

        // Verify token was refreshed
        $newToken = $lexware->getOAuth2Service()->getTokenStorage()->getToken();
        $this->assertEquals('refreshed_for_contacts', $newToken->getAccessToken());
    }

    public function test_handles_multiple_consecutive_api_calls_with_single_refresh()
    {
        $userId = 'refresh_user_3';
        $lexware = LexwareOfficeFactory::forUser($userId);

        $nonExpiredToken = new LexwareAccessToken(
            'valid_but_revoked_token',
            'Bearer',
            3600,
            'valid_refresh_token',
            ['profile', 'contacts'],
            new \DateTime() // Token is fresh but will be rejected by server (e.g., revoked)
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($nonExpiredToken);

        $refreshResponse = [
            'access_token' => 'single_refresh_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_single_refresh',
            'scope' => 'profile contacts'
        ];

        $profileResponse = [
            'organizationId' => 'multi_org_123',
            'companyName' => 'Multi Call Company',
            'created' => [
                'userId' => 'user_123',
                'userName' => 'Test User',
                'userEmail' => 'test@example.com',
                'date' => '2023-01-01T00:00:00+01:00'
            ],
            'connectionId' => 'conn_123'
        ];

        $contactsResponse = [
            'content' => [],
            'totalPages' => 0,
            'totalElements' => 0
        ];

        $mock = new MockHandler([
            // First API call fails with 401
            new Response(401, [], '{"error": "invalid_token"}'),
            // Token refresh succeeds
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
            // First API call retry succeeds
            new Response(200, ['Content-Type' => 'application/json'], json_encode($profileResponse)),
            // Second API call succeeds immediately (token is fresh)
            new Response(200, ['Content-Type' => 'application/json'], json_encode($contactsResponse)),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        // Make two API calls - should only refresh once
        $profile = $lexware->profile()->get();
        $contacts = $lexware->contacts()->filter();

        $this->assertEquals('multi_org_123', $profile->getOrganizationId());
        $this->assertEquals(0, $contacts->getTotalElements());

        // Verify token was refreshed only once
        $newToken = $lexware->getOAuth2Service()->getTokenStorage()->getToken();
        $this->assertEquals('single_refresh_token', $newToken->getAccessToken());
    }

    public function test_fails_when_refresh_token_is_invalid()
    {
        $userId = 'refresh_user_4';
        $lexware = LexwareOfficeFactory::forUser($userId);

        $expiredToken = new LexwareAccessToken(
            'expired_token',
            'Bearer',
            3600,
            'invalid_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($expiredToken);

        $request = new Request('POST', 'oauth2/token');
        $refreshException = new RequestException(
            'Invalid refresh token',
            $request,
            new Response(400, [], '{"error": "invalid_grant"}')
        );

        $mock = new MockHandler([
            new Response(401, [], '{"error": "invalid_token"}'),
            $refreshException,
        ]);

        $this->setMockHttpClient($lexware, $mock);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Failed to refresh token');

        $lexware->profile()->get();
    }

    public function test_fails_when_no_refresh_token_available()
    {
        $userId = 'refresh_user_5';
        $lexware = LexwareOfficeFactory::forUser($userId);

        $expiredToken = new LexwareAccessToken(
            'expired_token_no_refresh',
            'Bearer',
            3600,
            null, // No refresh token
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($expiredToken);

        $mock = new MockHandler([
            new Response(401, [], '{"error": "invalid_token"}'),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('{"error": "invalid_token"}');

        $lexware->profile()->get();
    }

    public function test_handles_token_expiring_soon_proactively()
    {
        $userId = 'refresh_user_6';
        $lexware = LexwareOfficeFactory::forUser($userId);

        // Token expires in 2 minutes (should trigger proactive refresh)
        $expiringSoonToken = new LexwareAccessToken(
            'expiring_soon_token',
            'Bearer',
            3600,
            'valid_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT3480S')) // 3480 seconds ago, 120 left
        );

        $lexware->getOAuth2Service()->getTokenStorage()->storeToken($expiringSoonToken);

        $refreshResponse = [
            'access_token' => 'proactively_refreshed_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_proactive_refresh',
            'scope' => 'profile'
        ];

        $profileResponse = [
            'organizationId' => 'proactive_org_123',
            'companyName' => 'Proactive Company',
            'created' => [
                'userId' => 'user_123',
                'userName' => 'Test User',
                'userEmail' => 'test@example.com',
                'date' => '2023-01-01T00:00:00+01:00'
            ],
            'connectionId' => 'conn_123'
        ];

        $mock = new MockHandler([
            // Token refresh happens before API call
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
            // API call succeeds with new token
            new Response(200, ['Content-Type' => 'application/json'], json_encode($profileResponse)),
        ]);

        $this->setMockHttpClient($lexware, $mock);

        // Get valid token should trigger proactive refresh
        $validToken = $lexware->getValidOAuth2Token();
        $this->assertEquals('proactively_refreshed_token', $validToken->getAccessToken());

        // Subsequent API call should use the fresh token
        $profile = $lexware->profile()->get();
        $this->assertEquals('proactive_org_123', $profile->getOrganizationId());
    }

    public function test_refresh_updates_token_storage_correctly()
    {
        $userId = 'refresh_user_7';
        $lexware = LexwareOfficeFactory::forUser($userId);

        $originalToken = new LexwareAccessToken(
            'original_access_token',
            'Bearer',
            3600,
            'original_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        $storage = $lexware->getOAuth2Service()->getTokenStorage();
        $storage->storeToken($originalToken);

        $refreshResponse = [
            'access_token' => 'updated_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 7200, // Different expiry
            'refresh_token' => 'updated_refresh_token',
            'scope' => 'profile contacts' // Additional scope
        ];

        $profileResponse = [
            'organizationId' => 'updated_org_123',
            'companyName' => 'Updated Company',
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

        $profile = $lexware->profile()->get();

        // Verify API call succeeded
        $this->assertEquals('updated_org_123', $profile->getOrganizationId());

        // Verify token was completely updated in storage
        $updatedToken = $storage->getToken();
        $this->assertEquals('updated_access_token', $updatedToken->getAccessToken());
        $this->assertEquals('updated_refresh_token', $updatedToken->getRefreshToken());
        $this->assertEquals(7200, $updatedToken->getExpiresIn());
        $this->assertEquals(['profile', 'contacts'], $updatedToken->getScopes());
        
        // Verify it's not expired
        $this->assertFalse($updatedToken->isExpired());
        $this->assertFalse($updatedToken->isExpiringSoon());
    }

    public function test_concurrent_requests_handle_refresh_gracefully()
    {
        $userId = 'refresh_user_8';
        $lexware1 = LexwareOfficeFactory::forUser($userId);
        $lexware2 = LexwareOfficeFactory::forUser($userId); // Same user, different instance

        $expiredToken = new LexwareAccessToken(
            'concurrent_expired_token',
            'Bearer',
            3600,
            'concurrent_refresh_token',
            ['profile'],
            (new \DateTime())->sub(new \DateInterval('PT2H'))
        );

        // Both instances share the same storage
        $lexware1->getOAuth2Service()->getTokenStorage()->storeToken($expiredToken);

        $refreshResponse = [
            'access_token' => 'concurrent_refreshed_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'concurrent_new_refresh',
            'scope' => 'profile'
        ];

        $profileResponse = [
            'organizationId' => 'concurrent_org_123',
            'companyName' => 'Concurrent Company',
            'created' => [
                'userId' => 'user_123',
                'userName' => 'Test User',
                'userEmail' => 'test@example.com',
                'date' => '2023-01-01T00:00:00+01:00'
            ],
            'connectionId' => 'conn_123'
        ];

        // First instance refreshes proactively, then makes API call
        $mock1 = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($profileResponse)),
        ]);

        // Second instance should see the refreshed token and make API call directly
        $mock2 = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($profileResponse)),
        ]);

        $this->setMockHttpClient($lexware1, $mock1);
        $this->setMockHttpClient($lexware2, $mock2);

        // First call triggers refresh
        $profile1 = $lexware1->profile()->get();
        
        // Second call should use refreshed token
        $profile2 = $lexware2->profile()->get();

        $this->assertEquals('concurrent_org_123', $profile1->getOrganizationId());
        $this->assertEquals('concurrent_org_123', $profile2->getOrganizationId());

        // Both should see the same refreshed token
        $token1 = $lexware1->getOAuth2Service()->getTokenStorage()->getToken();
        $token2 = $lexware2->getOAuth2Service()->getTokenStorage()->getToken();
        
        $this->assertEquals('concurrent_refreshed_token', $token1->getAccessToken());
        $this->assertEquals('concurrent_refreshed_token', $token2->getAccessToken());
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