<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use Fiber;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOAuthException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareTokenPersistenceException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\OAuthErrorCategory;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ReliableTokenRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'lexware-office.oauth2.refresh.lock_store' => 'array']);
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);
        Cache::flush();
        parent::tearDown();
    }

    public static function refreshEntryPoints(): array
    {
        return [['automatic'], ['explicit'], ['unauthorized']];
    }

    #[DataProvider('refreshEntryPoints')]
    public function test_overlapping_refreshes_wait_and_reuse_the_rotated_pair(string $entryPoint): void
    {
        $storage = new CacheTokenStorage('overlap');
        $storage->storeToken($this->expiredToken());
        $calls = 0;
        $handler = function () use (&$calls): Response {
            $calls++;
            Fiber::suspend('refresh-in-flight');

            return $this->tokenResponse();
        };
        $first = $this->service($storage, [$handler]);
        $second = $this->service(new CacheTokenStorage('overlap'), [$handler]);
        $invoke = fn (LexwareOAuth2Service $service) => match ($entryPoint) {
            'automatic' => $service->getValidAccessToken(),
            'explicit' => $service->refreshToken('ignored-stale-input'),
            'unauthorized' => $service->refreshAfterUnauthorized('sample-old-access'),
        };
        $holder = new Fiber(fn () => $invoke($first));
        $waiter = new Fiber(fn () => $invoke($second));
        Sleep::fake();
        Sleep::whenFakingSleep(fn () => Fiber::suspend('waiting-for-lock'));

        $this->assertSame('refresh-in-flight', $holder->start());
        $this->assertSame('waiting-for-lock', $waiter->start());
        $this->assertSame(1, $calls);
        $this->assertSame('sample-old-refresh', $storage->getToken()->getRefreshToken());
        $holder->resume();
        $this->assertSame('sample-new-refresh', $storage->getToken()->getRefreshToken());
        $waiter->resume();

        $this->assertTrue($holder->isTerminated());
        $this->assertTrue($waiter->isTerminated());
        $this->assertSame(1, $calls);
        $this->assertSame('sample-new-access', $holder->getReturn()->getAccessToken());
        $this->assertSame('sample-new-access', $waiter->getReturn()->getAccessToken());
    }

    public function test_timed_out_waiter_does_not_release_the_holders_lock(): void
    {
        $storage = new CacheTokenStorage('owner');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, []);
        config(['lexware-office.oauth2.refresh.lock_wait' => 0]);
        $holder = Cache::lock($service->getRefreshLockKey(), 90);
        $this->assertTrue($holder->get());

        try {
            $service->refreshToken();
            $this->fail('Expected lock timeout.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame(OAuthErrorCategory::Transient, $e->category);
            $this->assertTrue($holder->isOwnedByCurrentProcess());
            $this->assertFalse(Cache::lock($service->getRefreshLockKey(), 90)->get());
        } finally {
            $holder->release();
        }
    }

    public function test_lock_identity_is_stable_and_scoped_to_connection_and_environment(): void
    {
        $first = $this->service(new CacheTokenStorage('one'), []);
        $same = $this->service(new CacheTokenStorage('one'), []);
        $other = $this->service(new CacheTokenStorage('two'), []);

        $key = $first->getRefreshLockKey();
        $this->assertSame($key, $same->getRefreshLockKey());
        $this->assertNotSame($key, $other->getRefreshLockKey());
        config(['app.env' => 'another-environment']);
        $this->assertNotSame($key, $first->getRefreshLockKey());
    }

    public function test_minimum_lifetime_can_be_increased_by_a_scheduler(): void
    {
        $storage = new CacheTokenStorage('scheduler');
        $storage->storeToken(new LexwareAccessToken('sample-old-access', 'Bearer', 120, 'sample-old-refresh'));
        $service = $this->service($storage, [$this->tokenResponse()]);

        $this->assertSame('sample-old-access', $service->getValidAccessToken()->getAccessToken());
        $this->assertSame('sample-new-access', $service->getValidAccessToken(300)->getAccessToken());
    }

    public function test_explicit_input_cannot_override_current_stored_refresh_credentials(): void
    {
        $storage = new CacheTokenStorage('explicit');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [function ($request): Response {
            parse_str((string) $request->getBody(), $params);
            $this->assertSame('sample-old-refresh', $params['refresh_token']);

            return $this->tokenResponse();
        }]);

        $this->assertSame('sample-new-access', $service->refreshToken('ignored-stale-input')->getAccessToken());
    }

    public function test_successful_rotation_retries_local_storage_without_another_http_call(): void
    {
        $storage = new FailingRefreshStorage($this->expiredToken(), 1);
        $calls = 0;
        $service = $this->service($storage, [function () use (&$calls): Response {
            $calls++;

            return $this->tokenResponse();
        }]);

        $token = $service->refreshToken();

        $this->assertSame(1, $calls);
        $this->assertSame(2, $storage->writes);
        $this->assertSame($token, $storage->getToken());
    }

    public function test_persistence_failure_exposes_a_one_time_out_of_band_recovery_pair(): void
    {
        $storage = new FailingRefreshStorage($this->expiredToken(), 2);
        $calls = 0;
        $service = $this->service($storage, [function () use (&$calls): Response {
            $calls++;

            return $this->tokenResponse();
        }]);

        try {
            $service->refreshToken();
            $this->fail('Expected persistence failure.');
        } catch (LexwareTokenPersistenceException $e) {
            $this->assertSame(1, $calls);
            $this->assertSame(2, $storage->writes);
            $this->assertSame(200, $e->getStatusCode());
            $this->assertNull($e->getPrevious());
            $this->assertNull($e->getRequestException());
            foreach (['sample-old-access', 'sample-old-refresh', 'sample-new-access', 'sample-new-refresh', 'sample-client-secret'] as $secret) {
                $this->assertStringNotContainsString($secret, (string) $e);
                $this->assertStringNotContainsString($secret, serialize($e));
                $this->assertStringNotContainsString($secret, json_encode($e->context()));
            }
            $copy = unserialize(serialize($e));
            $this->assertSame($e->context(), $copy->context());
            $this->assertNull($copy->takeToken());
            $recovered = $e->takeToken();
            $this->assertSame('sample-new-refresh', $recovered->getRefreshToken());
            $this->assertNull($e->takeToken());
            $storage->storeToken($recovered);
            $this->assertSame('sample-new-access', $storage->getToken()->getAccessToken());
        }
    }

    public function test_ambiguous_local_write_acknowledgement_does_not_lose_the_new_pair(): void
    {
        $storage = new FailingRefreshStorage($this->expiredToken(), 1, true);
        $service = $this->service($storage, [$this->tokenResponse()]);

        $this->assertSame('sample-new-access', $service->refreshToken()->getAccessToken());
        $this->assertSame(1, $storage->writes);
    }

    public function test_stale_rotation_response_cannot_overwrite_a_newer_pair(): void
    {
        $storage = new CacheTokenStorage('stale-response');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [function () use ($storage): Response {
            $storage->storeToken(new LexwareAccessToken('newer-access', 'Bearer', 3600, 'newer-refresh'));

            return $this->tokenResponse();
        }]);

        try {
            $service->refreshToken();
            $this->fail('Expected stale-write rejection.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame(OAuthErrorCategory::OutcomeUnknown, $e->category);
            $this->assertSame('newer-refresh', $storage->getToken()->getRefreshToken());
        }
    }

    public function test_invalid_grant_does_not_require_reauthorization_when_credentials_changed_in_flight(): void
    {
        $storage = new CacheTokenStorage('changed-grant');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [function () use ($storage): Response {
            $storage->storeToken(new LexwareAccessToken('newer-access', 'Bearer', 3600, 'newer-refresh'));

            return new Response(400, [], '{"error":"invalid_grant"}');
        }]);

        $this->assertSame('newer-access', $service->refreshToken()->getAccessToken());
    }

    public function test_http_date_retry_after_is_exposed_as_seconds(): void
    {
        $storage = new CacheTokenStorage('retry-date');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [new Response(429, ['Retry-After' => gmdate('D, d M Y H:i:s', time() + 60).' GMT'], '{}')]);

        try {
            $service->refreshToken();
            $this->fail('Expected rate-limit error.');
        } catch (LexwareOAuthException $e) {
            $this->assertGreaterThanOrEqual(58, $e->getRetryAfter());
            $this->assertLessThanOrEqual(60, $e->getRetryAfter());
        }
    }

    public static function endpointErrors(): array
    {
        return [
            'invalid grant' => [400, ['error' => 'invalid_grant'], OAuthErrorCategory::InvalidGrant, 'invalid_grant'],
            'invalid client' => [401, ['error' => 'invalid_client'], OAuthErrorCategory::ClientConfiguration, 'invalid_client'],
            'disabled client' => [400, ['error' => 'unauthorized_client'], OAuthErrorCategory::ClientConfiguration, 'unauthorized_client'],
            'bad request' => [400, ['error' => 'invalid_request'], OAuthErrorCategory::ClientConfiguration, 'invalid_request'],
            'unknown error' => [400, ['error' => 'arbitrary-server-text'], OAuthErrorCategory::ClientConfiguration, null],
            'rate limit' => [429, [], OAuthErrorCategory::Transient, null],
            'server error' => [503, [], OAuthErrorCategory::Transient, null],
            'unparseable' => [400, 'not-json', OAuthErrorCategory::ClientConfiguration, null],
        ];
    }

    #[DataProvider('endpointErrors')]
    public function test_endpoint_errors_are_structured_and_sanitized(int $status, array|string $body, OAuthErrorCategory $category, ?string $oauthError): void
    {
        $storage = new CacheTokenStorage('errors');
        $storage->storeToken($this->expiredToken());
        if (is_array($body)) {
            $body['error_description'] = str_repeat('sample-old-refresh sample-client-secret Bearer sample-old-access ', 20);
            $body['error_uri'] = 'https://example.com/sample-client-secret';
            $body = json_encode($body);
        }
        $service = $this->service($storage, [new Response($status, ['Retry-After' => '45'], $body)]);

        try {
            $service->refreshToken();
            $this->fail('Expected token endpoint failure.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame($status, $e->getStatusCode());
            $this->assertSame($category, $e->category);
            $this->assertSame($oauthError, $e->oauthError);
            $this->assertSame(45, $e->getRetryAfter());
            $this->assertLessThanOrEqual(200, strlen($e->oauthErrorDescription ?? ''));
            $this->assertNull($e->getPrevious());
            foreach (['sample-old-refresh', 'sample-old-access', 'sample-client-secret', base64_encode('sample-client:sample-client-secret')] as $secret) {
                $this->assertStringNotContainsString($secret, serialize($e));
                $this->assertStringNotContainsString($secret, json_encode($e->context()));
            }
            $this->assertSame('sample-old-refresh', $storage->getToken()->getRefreshToken());
        }
    }

    public static function transportFailures(): array
    {
        return [[6, OAuthErrorCategory::Transient], [7, OAuthErrorCategory::Transient], [28, OAuthErrorCategory::OutcomeUnknown], [52, OAuthErrorCategory::OutcomeUnknown], [56, OAuthErrorCategory::OutcomeUnknown]];
    }

    #[DataProvider('transportFailures')]
    public function test_transport_failures_do_not_blindly_retry_rotating_grants(int $errno, OAuthErrorCategory $category): void
    {
        $storage = new CacheTokenStorage('transport');
        $storage->storeToken($this->expiredToken());
        $failure = new ConnectException('sample-client-secret sample-old-refresh', new Request('POST', 'https://example.com', ['Authorization' => 'Basic sample-secret']), null, ['errno' => $errno]);
        $service = $this->service($storage, [$failure]);

        try {
            $service->refreshToken();
            $this->fail('Expected transport failure.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame($category, $e->category);
            $this->assertSame(0, $e->getStatusCode());
            $this->assertNull($e->getPrevious());
            $this->assertStringNotContainsString('sample-client-secret', serialize($e));
            $this->assertStringNotContainsString('sample-old-refresh', serialize($e));
        }
    }

    public static function invalidResponses(): array
    {
        return [
            [null],
            [['access_token' => "sample-new-access\r\nunsafe", 'refresh_token' => 'sample-new-refresh', 'expires_in' => 3600]],
            [[]],
            [['access_token' => 'sample-new-access', 'expires_in' => 3600]],
            [['access_token' => 123, 'refresh_token' => 'sample-new-refresh', 'expires_in' => 3600]],
            [['access_token' => 'sample-new-access', 'refresh_token' => '', 'expires_in' => 3600]],
            [['access_token' => 'sample-new-access', 'refresh_token' => 'sample-new-refresh', 'expires_in' => '3600']],
            [['access_token' => 'sample-new-access', 'refresh_token' => 'sample-new-refresh', 'expires_in' => 0]],
            [['access_token' => 'sample-new-access', 'refresh_token' => 'sample-new-refresh', 'expires_in' => 3600, 'token_type' => 'unsafe-type']],
        ];
    }

    #[DataProvider('invalidResponses')]
    public function test_invalid_success_response_is_not_persisted(mixed $data): void
    {
        $storage = new CacheTokenStorage('invalid-response');
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [new Response(200, [], json_encode($data))]);

        try {
            $service->refreshToken();
            $this->fail('Expected response validation failure.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame(OAuthErrorCategory::ClientConfiguration, $e->category);
            $this->assertSame('sample-old-refresh', $storage->getToken()->getRefreshToken());
        }
    }

    public function test_basic_authentication_and_independent_authorization_server_url(): void
    {
        config([
            'lexware-office.base_url' => 'https://resource.example.com/v1',
            'lexware-office.oauth2.base_url' => 'https://authorization.example.com',
            'lexware-office.oauth2.client_id' => 'sample-client',
            'lexware-office.oauth2.client_secret' => 'sample-client-secret',
            'lexware-office.oauth2.redirect_uri' => 'https://example.com/callback',
        ]);
        $service = LexwareOfficeFactory::createOAuth2Service('example');
        $service->getTokenStorage()->storeToken($this->expiredToken());
        $assertRequest = function ($request): Response {
            $this->assertSame('https://authorization.example.com/oauth2/token', (string) $request->getUri());
            $this->assertSame('Basic '.base64_encode('sample-client:sample-client-secret'), $request->getHeaderLine('Authorization'));
            parse_str((string) $request->getBody(), $params);
            $this->assertArrayNotHasKey('client_id', $params);
            $this->assertArrayNotHasKey('client_secret', $params);

            return $this->tokenResponse();
        };
        $this->setHttpClient($service, new Client(['handler' => HandlerStack::create(new MockHandler([$assertRequest, $assertRequest]))]));

        $this->assertStringStartsWith('https://authorization.example.com/oauth2/authorize?', $service->getAuthorizationUrl('example-state')->getUrl());
        $service->refreshToken();
        $service->exchangeCodeForToken('sample-code', 'example-state');
    }

    public static function unauthorizedResponses(): array
    {
        return [[true, false], [false, false], [false, true]];
    }

    #[DataProvider('unauthorizedResponses')]
    public function test_401_reuses_replaced_token_or_refreshes_and_retries_only_once(bool $alreadyReplaced, bool $rejectRetry): void
    {
        $storage = new CacheTokenStorage('unauthorized');
        $storage->storeToken(new LexwareAccessToken('sample-old-access', 'Bearer', 3600, 'sample-old-refresh'));
        $refreshCalls = 0;
        $service = $this->service($storage, [function () use (&$refreshCalls): Response {
            $refreshCalls++;

            return $this->tokenResponse();
        }]);
        $client = new LexwareOffice('https://resource.example.com/v1', 'sample-api-key', 'test', 0);
        $client->setOAuth2Service($service);
        $requests = [];
        $handler = new MockHandler([
            function ($request) use ($alreadyReplaced, $storage, &$requests): Response {
                $requests[] = $request->getHeaderLine('Authorization');
                if ($alreadyReplaced) {
                    $storage->storeToken(new LexwareAccessToken('sample-new-access', 'Bearer', 3600, 'sample-new-refresh'));
                }

                return new Response(401, [], '{"message":"Unauthorized"}');
            },
            function ($request) use (&$requests, $rejectRetry): Response {
                $requests[] = $request->getHeaderLine('Authorization');

                return new Response($rejectRetry ? 401 : 200, [], '{"ok":true}');
            },
        ]);
        $client->setClient(new Client(['handler' => HandlerStack::create($handler)]));

        try {
            $this->assertSame(['ok' => true], $client->get('https://resource.example.com/v1/profile'));
            $this->assertFalse($rejectRetry);
        } catch (LexwareOfficeApiException $e) {
            $this->assertTrue($rejectRetry);
            $this->assertSame(401, $e->getStatusCode());
        }

        $this->assertSame(['Bearer sample-old-access', 'Bearer sample-new-access'], $requests);
        $this->assertSame($alreadyReplaced ? 0 : 1, $refreshCalls);
        $this->assertSame(0, $handler->count());
    }

    public function test_conditional_storage_failure_retries_only_the_received_pair(): void
    {
        $storage = new class('conditional-retry') extends CacheTokenStorage
        {
            public int $writes = 0;

            public function storeTokenIfCurrent(#[\SensitiveParameter] LexwareAccessToken $token, #[\SensitiveParameter] string $refreshToken): bool
            {
                if (++$this->writes === 1) {
                    throw new \RuntimeException('Synthetic storage failure.');
                }

                return parent::storeTokenIfCurrent($token, $refreshToken);
            }
        };
        $storage->storeToken($this->expiredToken());
        $service = $this->service($storage, [$this->tokenResponse()]);

        $this->assertSame('sample-new-access', $service->refreshToken()->getAccessToken());
        $this->assertSame(2, $storage->writes);
        $this->assertSame('sample-new-refresh', $storage->getToken()->getRefreshToken());
    }

    public function test_401_surfaces_structured_refresh_failure_without_retrying_the_resource(): void
    {
        $storage = new CacheTokenStorage('unauthorized-error');
        $storage->storeToken(new LexwareAccessToken('sample-old-access', 'Bearer', 3600, 'sample-old-refresh'));
        $service = $this->service($storage, [new Response(400, [], '{"error":"unauthorized_client"}')]);
        $client = new LexwareOffice('https://resource.example.com/v1', 'sample-api-key', 'test', 0);
        $client->setOAuth2Service($service);
        $handler = new MockHandler([new Response(401, [], '{"message":"Unauthorized"}')]);
        $client->setClient(new Client(['handler' => HandlerStack::create($handler)]));

        try {
            $client->get('https://resource.example.com/v1/profile');
            $this->fail('Expected structured refresh failure.');
        } catch (LexwareOAuthException $e) {
            $this->assertSame(OAuthErrorCategory::ClientConfiguration, $e->category);
            $this->assertSame('unauthorized_client', $e->oauthError);
            $this->assertSame(0, $handler->count());
            $this->assertSame('sample-old-refresh', $storage->getToken()->getRefreshToken());
        }
    }

    public function test_factories_and_binding_accept_environment_sourced_numeric_config(): void
    {
        config(['lexware-office.max_requests_per_minute' => '25']);

        $this->assertInstanceOf(LexwareOffice::class, LexwareOfficeFactory::forUser('numeric-config'));
        $this->assertInstanceOf(LexwareOffice::class, LexwareOfficeFactory::withApiKey('sample-api-key'));
        $this->assertInstanceOf(LexwareOffice::class, app('lexware-office'));
    }

    private function service(LexwareTokenStorage $storage, array $responses): LexwareOAuth2Service
    {
        $service = new LexwareOAuth2Service('sample-client', 'sample-client-secret', 'https://example.com/callback');
        $service->setTokenStorage($storage);
        $this->setHttpClient($service, new Client(['handler' => HandlerStack::create(new MockHandler($responses))]));

        return $service;
    }

    private function setHttpClient(LexwareOAuth2Service $service, Client $client): void
    {
        (new \ReflectionProperty($service, 'httpClient'))->setValue($service, $client);
    }

    private function expiredToken(): LexwareAccessToken
    {
        return new LexwareAccessToken('sample-old-access', 'Bearer', 3600, 'sample-old-refresh', [], new \DateTime('-2 hours'));
    }

    private function tokenResponse(): Response
    {
        return new Response(200, [], json_encode([
            'access_token' => 'sample-new-access',
            'refresh_token' => 'sample-new-refresh',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ]));
    }
}

class FailingRefreshStorage implements LexwareTokenStorage
{
    public int $writes = 0;

    public function __construct(private LexwareAccessToken $token, private int $failures, private bool $writeBeforeFailure = false) {}

    public function storeToken(#[\SensitiveParameter] LexwareAccessToken $token): void
    {
        $this->writes++;
        if ($this->writeBeforeFailure) {
            $this->token = $token;
        }
        if ($this->writes <= $this->failures) {
            throw new \RuntimeException('Storage failure with sample-new-refresh');
        }
        $this->token = $token;
    }

    public function getToken(): ?LexwareAccessToken
    {
        return $this->token;
    }

    public function clearToken(): void {}
}
