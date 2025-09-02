<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class OAuth2ServiceTest extends TestCase
{
    protected LexwareOAuth2Service $oauth2Service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oauth2Service = new LexwareOAuth2Service(
            'test_client_id',
            'test_client_secret',
            'https://example.com/callback',
            'https://api.lexoffice.de',
            ['profile', 'contacts']
        );
    }

    public function test_generates_authorization_url_with_pkce()
    {
        $authUrl = $this->oauth2Service->getAuthorizationUrl('test_state');

        $this->assertStringContainsString('https://api.lexoffice.de/oauth2/authorize', $authUrl->getUrl());
        $this->assertStringContainsString('client_id=test_client_id', $authUrl->getUrl());
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $authUrl->getUrl());
        $this->assertStringContainsString('scope=profile+contacts', $authUrl->getUrl());
        $this->assertStringContainsString('state=test_state', $authUrl->getUrl());
        $this->assertStringContainsString('code_challenge=', $authUrl->getUrl());
        $this->assertStringContainsString('code_challenge_method=S256', $authUrl->getUrl());
        $this->assertStringContainsString('response_type=code', $authUrl->getUrl());

        $this->assertEquals('test_state', $authUrl->getState());
        $this->assertNotEmpty($authUrl->getCodeVerifier());
    }

    public function test_generates_random_state_when_not_provided()
    {
        $authUrl1 = $this->oauth2Service->getAuthorizationUrl();
        $authUrl2 = $this->oauth2Service->getAuthorizationUrl();

        $this->assertNotEquals($authUrl1->getState(), $authUrl2->getState());
        $this->assertEquals(32, strlen($authUrl1->getState()));
        $this->assertEquals(32, strlen($authUrl2->getState()));
    }

    public function test_exchanges_authorization_code_for_token()
    {
        // Store PKCE data in cache first
        Cache::put('lexware_pkce_test_state', 'test_code_verifier', now()->addMinutes(10));

        $tokenResponse = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh_token_456',
            'scope' => 'profile contacts',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($tokenResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Use reflection to set the HTTP client
        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $token = $this->oauth2Service->exchangeCodeForToken('auth_code_123', 'test_state');

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertEquals('refresh_token_456', $token->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $token->getScopes());
    }

    public function test_throws_exception_for_invalid_state()
    {
        // No PKCE data stored for this state
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Invalid or expired state parameter');

        $this->oauth2Service->exchangeCodeForToken('auth_code_123', 'invalid_state');
    }

    public function test_refreshes_access_token()
    {
        $tokenResponse = [
            'access_token' => 'new_access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_token_456',
            'scope' => 'profile contacts',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($tokenResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Use reflection to set the HTTP client
        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $token = $this->oauth2Service->refreshToken('old_refresh_token');

        $this->assertEquals('new_access_token_123', $token->getAccessToken());
        $this->assertEquals('new_refresh_token_456', $token->getRefreshToken());
    }

    public function test_refreshes_using_stored_token()
    {
        // Store an existing token
        $existingToken = new LexwareAccessToken(
            'old_access_token',
            'Bearer',
            3600,
            'stored_refresh_token',
            ['profile']
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($existingToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $tokenResponse = [
            'access_token' => 'refreshed_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refreshed_refresh_token',
            'scope' => 'profile',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($tokenResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $token = $this->oauth2Service->refreshToken();

        $this->assertEquals('refreshed_access_token', $token->getAccessToken());
        $this->assertEquals('refreshed_refresh_token', $token->getRefreshToken());
    }

    public function test_throws_exception_when_no_refresh_token_available()
    {
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('No refresh token available');

        $this->oauth2Service->refreshToken();
    }

    public function test_gets_valid_access_token_when_not_expired()
    {
        $validToken = new LexwareAccessToken(
            'valid_access_token',
            'Bearer',
            3600,
            'refresh_token',
            ['profile'],
            new \DateTime // Created now, so not expired
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($validToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $token = $this->oauth2Service->getValidAccessToken();

        $this->assertNotNull($token);
        $this->assertEquals('valid_access_token', $token->getAccessToken());
    }

    public function test_refreshes_expired_token_automatically()
    {
        $expiredToken = new LexwareAccessToken(
            'expired_access_token',
            'Bearer',
            3600,
            'refresh_token',
            ['profile'],
            (new \DateTime)->sub(new \DateInterval('PT2H')) // Created 2 hours ago, so expired
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($expiredToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $refreshResponse = [
            'access_token' => 'auto_refreshed_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_token',
            'scope' => 'profile',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($refreshResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $token = $this->oauth2Service->getValidAccessToken();

        $this->assertNotNull($token);
        $this->assertEquals('auto_refreshed_token', $token->getAccessToken());
    }

    public function test_returns_null_when_token_expired_and_no_refresh_token()
    {
        $expiredToken = new LexwareAccessToken(
            'expired_access_token',
            'Bearer',
            3600,
            null, // No refresh token
            ['profile'],
            (new \DateTime)->sub(new \DateInterval('PT2H'))
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($expiredToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $token = $this->oauth2Service->getValidAccessToken();

        $this->assertNull($token);
    }

    public function test_revokes_token_successfully()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $result = $this->oauth2Service->revokeToken('token_to_revoke');

        $this->assertTrue($result);
    }

    public function test_revokes_stored_token_when_no_token_provided()
    {
        $existingToken = new LexwareAccessToken(
            'stored_access_token',
            'Bearer',
            3600,
            'refresh_token'
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($existingToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $mock = new MockHandler([
            new Response(200),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $result = $this->oauth2Service->revokeToken();

        $this->assertTrue($result);

        // Token should be cleared from storage
        $this->assertNull($tokenStorage->getToken());
    }

    public function test_clears_token_even_when_revocation_fails()
    {
        $existingToken = new LexwareAccessToken(
            'stored_access_token',
            'Bearer',
            3600,
            'refresh_token'
        );

        $tokenStorage = new CacheTokenStorage('test_key');
        $tokenStorage->storeToken($existingToken);
        $this->oauth2Service->setTokenStorage($tokenStorage);

        $request = new Request('POST', 'oauth2/revoke');
        $exception = new RequestException('Server error', $request);

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $result = $this->oauth2Service->revokeToken();

        $this->assertFalse($result);

        // Token should still be cleared from storage
        $this->assertNull($tokenStorage->getToken());
    }

    public function test_handles_token_exchange_http_errors()
    {
        Cache::put('lexware_pkce_test_state', 'test_code_verifier', now()->addMinutes(10));

        $request = new Request('POST', 'oauth2/token');
        $exception = new RequestException('Bad request', $request, new Response(400, [], '{"error": "invalid_grant"}'));

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Failed to exchange authorization code');

        $this->oauth2Service->exchangeCodeForToken('invalid_code', 'test_state');
    }

    public function test_handles_token_refresh_http_errors()
    {
        $request = new Request('POST', 'oauth2/token');
        $exception = new RequestException('Unauthorized', $request, new Response(401, [], '{"error": "invalid_token"}'));

        $mock = new MockHandler([$exception]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->oauth2Service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setValue($this->oauth2Service, $client);

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Failed to refresh token');

        $this->oauth2Service->refreshToken('invalid_refresh_token');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
