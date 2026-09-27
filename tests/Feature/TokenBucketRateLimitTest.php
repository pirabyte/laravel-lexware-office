<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\RateLimiting\RateLimitBucket;
use Pirabyte\LaravelLexwareOffice\RateLimiting\TokenBucketRateLimiter;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class TokenBucketRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_capacity_is_reserved_before_a_request_is_sent(): void
    {
        $bucket = new RateLimitBucket('connection', 'shared-connection', 1, 1);
        $otherClient = $this->clientWithResponses(new Response(200, [], '{}'));
        $otherClient->setRequestRateLimiter(new TokenBucketRateLimiter($bucket));

        $client = new LexwareOffice('https://api.lexware.io/v1', 'test-key');
        $client->setRequestRateLimiter(new TokenBucketRateLimiter($bucket));
        $client->setClient(new Client(['handler' => HandlerStack::create(new MockHandler([
            function () use ($otherClient): Response {
                try {
                    $otherClient->get('contacts');
                    $this->fail('A second request used capacity reserved by the first request.');
                } catch (LexwareOfficeApiException $exception) {
                    $this->assertSame(429, $exception->getStatusCode());
                    $this->assertGreaterThanOrEqual(1, $exception->getRetryAfter());
                }

                return new Response(200, [], '{}');
            },
        ]))]));

        $this->assertSame([], $client->get('contacts'));
    }

    public function test_connection_and_client_buckets_are_shared_without_spending_on_denial(): void
    {
        $clientBucket = new RateLimitBucket('client', 'partner-client', 2, 2, perEndpoint: true);
        $firstConnection = new TokenBucketRateLimiter(
            new RateLimitBucket('connection', 'first', 1, 1, perEndpoint: true),
            $clientBucket,
        );
        $secondConnection = new TokenBucketRateLimiter(
            new RateLimitBucket('connection', 'second', 1, 2, perEndpoint: true),
            $clientBucket,
        );

        $firstConnection->reserve('GET', 'contacts/123e4567-e89b-12d3-a456-426614174000');

        try {
            $firstConnection->reserve('GET', 'contacts/123e4567-e89b-12d3-a456-426614174001');
            $this->fail('The first connection exceeded its capacity.');
        } catch (LexwareOfficeApiException $exception) {
            $this->assertSame(429, $exception->getStatusCode());
        }

        $secondConnection->reserve('GET', 'contacts/123e4567-e89b-12d3-a456-426614174002');

        $this->expectException(LexwareOfficeApiException::class);
        $secondConnection->reserve('GET', 'contacts/123e4567-e89b-12d3-a456-426614174003');
    }

    public function test_endpoint_groups_are_isolated_when_configured(): void
    {
        $limiter = new TokenBucketRateLimiter(new RateLimitBucket('connection', 'one', 1, 1, perEndpoint: true));

        $limiter->reserve('GET', 'contacts');
        $limiter->reserve('POST', 'contacts');

        $this->expectException(LexwareOfficeApiException::class);
        $limiter->reserve('GET', 'contacts');
    }

    public function test_different_credentials_have_independent_capacity(): void
    {
        $firstKey = new TokenBucketRateLimiter(new RateLimitBucket('api-key', 'first-key', 1, 1));
        $secondKey = new TokenBucketRateLimiter(new RateLimitBucket('api-key', 'second-key', 1, 1));

        $firstKey->reserve('GET', 'contacts');
        $secondKey->reserve('GET', 'contacts');

        $this->expectException(LexwareOfficeApiException::class);
        $firstKey->reserve('GET', 'vouchers');
    }

    public function test_global_bucket_refills_after_its_interval(): void
    {
        $limiter = new TokenBucketRateLimiter(new RateLimitBucket('api-key', 'one', 5, 1));

        $limiter->reserve('GET', 'contacts');

        try {
            $limiter->reserve('POST', 'vouchers');
            $this->fail('The global bucket should cover every endpoint.');
        } catch (LexwareOfficeApiException $exception) {
            $this->assertSame(1, $exception->getRetryAfter());
        }

        usleep(300_000);

        $limiter->reserve('POST', 'vouchers');
    }

    public function test_unauthorized_retry_reserves_a_second_slot(): void
    {
        $token = new LexwareAccessToken('access-token', 'Bearer', 3600, 'refresh-token');
        $oauth = Mockery::mock(LexwareOAuth2Service::class);
        $oauth->shouldReceive('getValidAccessToken')->andReturn($token);
        $oauth->shouldReceive('refreshToken')->once()->andReturn($token);

        $client = $this->clientWithResponses(
            new Response(401, [], '{"message":"Unauthorized"}'),
            new Response(200, [], '{}'),
        );
        $client->setOAuth2Service($oauth);
        $client->setRequestRateLimiter(new TokenBucketRateLimiter(
            new RateLimitBucket('connection', 'oauth-retry', 1, 2),
        ));

        $this->assertSame([], $client->get('contacts'));

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(429);
        $client->get('contacts');
    }

    public function test_failed_outbound_attempt_uses_capacity_and_keeps_retry_after(): void
    {
        $request = new Request('GET', 'contacts');
        $client = $this->clientWithResponses(new RequestException(
            'Too many requests',
            $request,
            new Response(429, ['Retry-After' => '17'], '{"message":"Too many requests"}'),
        ));
        $client->setRequestRateLimiter(new TokenBucketRateLimiter(
            new RateLimitBucket('connection', 'failed-request', 1, 1),
        ));

        try {
            $client->get('contacts');
            $this->fail('Lexware should have returned a 429.');
        } catch (LexwareOfficeApiException $exception) {
            $this->assertSame(17, $exception->getRetryAfter());
        }

        try {
            $client->get('contacts');
            $this->fail('The failed outbound attempt should have used the only slot.');
        } catch (LexwareOfficeApiException $exception) {
            $this->assertSame(429, $exception->getStatusCode());
            $this->assertGreaterThanOrEqual(1, $exception->getRetryAfter());
        }
    }

    public function test_limited_requests_do_not_follow_redirects_without_another_reservation(): void
    {
        $requests = [
            'GET' => fn (LexwareOffice $client): array => $client->get('contacts'),
            'POST' => fn (LexwareOffice $client): array => $client->post('contacts', []),
            'MULTIPART' => fn (LexwareOffice $client): array => $client->postMultipart('vouchers/files', []),
            'PUT' => fn (LexwareOffice $client): array => $client->put('contacts/123', []),
            'DELETE' => function (LexwareOffice $client): void {
                $client->delete('contacts/123');
            },
        ];

        foreach ($requests as $method => $send) {
            $handler = new MockHandler([
                new Response(302, ['Location' => 'https://api.lexware.io/v1/contacts'], ''),
                new Response(200, [], '{}'),
            ]);
            $client = new LexwareOffice('https://api.lexware.io/v1', 'test-key');
            $client->setClient(new Client(['handler' => HandlerStack::create($handler)]));
            $client->setRequestRateLimiter(new TokenBucketRateLimiter(
                new RateLimitBucket('connection', 'redirect-'.$method, 1, 1),
            ));

            $send($client);

            $this->assertCount(1, $handler, $method.' followed a redirect without another reservation.');
        }
    }

    public function test_existing_client_still_follows_redirects(): void
    {
        $handler = new MockHandler([
            new Response(302, ['Location' => 'https://api.lexware.io/v1/contacts'], ''),
            new Response(200, [], '{}'),
        ]);
        $client = new LexwareOffice('https://api.lexware.io/v1', 'test-key', maxRequestsPerMinute: 0);
        $client->setClient(new Client(['handler' => HandlerStack::create($handler)]));

        $this->assertSame([], $client->get('contacts'));
        $this->assertCount(0, $handler);
    }

    private function clientWithResponses(Response|RequestException ...$responses): LexwareOffice
    {
        $client = new LexwareOffice('https://api.lexware.io/v1', 'test-key');
        $client->setClient(new Client(['handler' => HandlerStack::create(new MockHandler($responses))]));

        return $client;
    }
}
