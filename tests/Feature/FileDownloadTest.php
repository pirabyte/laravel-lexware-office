<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\Responses\FileDownload;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class FileDownloadTest extends TestCase
{
    public function test_downloads_all_file_endpoints_with_unconsumed_streams_and_accept_headers(): void
    {
        $binary = "%PDF-1.7\n\x00\xFF\n";
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/pdf; charset=binary', 'Content-Disposition' => 'attachment; filename="invoice.pdf"'], new NoSeekStream(Utils::streamFor($binary))),
            new Response(200, ['Content-Type' => 'application/xml', 'Content-Disposition' => "attachment; filename=preview.xml; filename*=UTF-8''Rechnungskorrektur%20%C3%84.xml"], '<Invoice/>'),
            new Response(200, [], $binary),
        ]));
        $handler->push(Middleware::history($history));
        $client = $this->clientWith($handler);

        $invoice = $client->invoices()->file('invoice-id', FileFormat::PDF);
        $credit = $client->creditNotes()->file('credit-id', FileFormat::XML);
        $file = $client->files()->download('file-id');

        $this->assertSame(0, $invoice->stream->tell());
        $this->assertSame($binary, $invoice->stream->getContents());
        $this->assertSame('application/pdf', $invoice->mimeType);
        $this->assertSame('invoice.pdf', $invoice->filename);
        $this->assertSame('<Invoice/>', $credit->stream->getContents());
        $this->assertSame('Rechnungskorrektur Ä.xml', $credit->filename);
        $this->assertNull($file->mimeType);
        $this->assertNull($file->filename);
        $this->assertSame($binary, $file->stream->getContents());
        foreach (['/v1/invoices/invoice-id/file', '/v1/credit-notes/credit-id/file', '/v1/files/file-id'] as $index => $path) {
            $this->assertSame($path, $history[$index]['request']->getUri()->getPath());
            $this->assertSame('GET', $history[$index]['request']->getMethod());
            $this->assertTrue($history[$index]['options']['stream']);
            $this->assertSame('Bearer test-key', $history[$index]['request']->getHeaderLine('Authorization'));
        }
        $this->assertSame('application/pdf', $history[0]['request']->getHeaderLine('Accept'));
        $this->assertSame('application/xml', $history[1]['request']->getHeaderLine('Accept'));
        $this->assertSame('*/*', $history[2]['request']->getHeaderLine('Accept'));
    }

    #[DataProvider('filenames')]
    public function test_suggested_filename_is_a_basename(string $header, ?string $expected): void
    {
        $download = FileDownload::fromResponse(new Response(200, ['Content-Disposition' => $header]));
        $this->assertSame($expected, $download->filename);
    }

    public static function filenames(): array
    {
        return [
            ['attachment; filename="../../invoice.pdf"', 'invoice.pdf'],
            ["attachment; filename*=UTF-8''..%5C..%5Cinvoice.xml", 'invoice.xml'],
            ["attachment; filename*=UTF-8''..", null],
            ['attachment', null],
        ];
    }

    #[DataProvider('errorStatuses')]
    public function test_binary_http_errors_use_the_existing_api_exception(int $status): void
    {
        $client = $this->clientWith(HandlerStack::create(new MockHandler([
            new Response($status, ['Content-Type' => 'application/json', 'Retry-After' => '7'], '{"message":"Download failed"}'),
        ])));

        try {
            $client->invoices()->file('invoice-id');
            $this->fail('Expected API exception');
        } catch (LexwareOfficeApiException $e) {
            $this->assertSame($status, $e->getCode());
            $this->assertStringContainsString('Download failed', $e->getMessage());
            if ($status === 429) {
                $this->assertSame(7, $e->getResponseData()['retryAfter']);
            }
        }
    }

    public static function errorStatuses(): array
    {
        return [[401], [404], [406], [409], [429]];
    }

    public function test_binary_download_uses_oauth_refresh_and_preserves_the_retry_body(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(401, [], '{"message":"expired"}'),
            new Response(200, ['Content-Type' => 'application/xml'], '<Invoice/>'),
        ]));
        $handler->push(Middleware::history($history));
        $client = $this->clientWith($handler);
        $oldToken = new LexwareAccessToken('old', 'Bearer', 3600, 'refresh');
        $newToken = new LexwareAccessToken('new', 'Bearer', 3600, 'refresh');
        $oauth = Mockery::mock(LexwareOAuth2Service::class);
        $oauth->shouldReceive('getValidAccessToken')->twice()->andReturn($oldToken, $newToken);
        $oauth->shouldReceive('refreshToken')->once()->andReturn($newToken);
        $client->setOAuth2Service($oauth);

        $download = $client->invoices()->file('invoice-id', FileFormat::XML);

        $this->assertSame('<Invoice/>', $download->stream->getContents());
        $this->assertCount(2, $history);
        $this->assertSame('Bearer old', $history[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame('Bearer new', $history[1]['request']->getHeaderLine('Authorization'));
        $this->assertSame('application/xml', $history[1]['request']->getHeaderLine('Accept'));
    }

    public function test_binary_download_shares_rate_limit_accounting_and_checks_capacity(): void
    {
        RateLimiter::shouldReceive('tooManyAttempts')->once()->with('download-test', 1)->andReturn(false);
        RateLimiter::shouldReceive('hit')->once()->with('download-test', 60);
        $client = $this->clientWith(HandlerStack::create(new MockHandler([new Response(200, [], 'file')])));
        $client->setRateLimitKey('download-test');
        $client->setRateLimit(1);
        $this->assertSame('file', $client->files()->download('id')->stream->getContents());

        RateLimiter::shouldReceive('tooManyAttempts')->once()->with('download-test', 1)->andReturn(true);
        RateLimiter::shouldReceive('availableIn')->once()->with('download-test')->andReturn(20);
        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionCode(429);
        $client->files()->download('id');
    }

    public function test_post_without_query_and_non_json_get_remain_compatible(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response(201, [], '{"id":"created"}'), new Response(200, [], 'raw-content'), new Response(201, [], '{"id":"created"}')]));
        $handler->push(Middleware::history($history));
        $client = $this->clientWith($handler);

        $this->assertSame(['id' => 'created'], $client->post('contacts', ['name' => 'Kunde']));
        $this->assertSame('', $history[0]['request']->getUri()->getQuery());
        $this->assertSame(['raw' => 'raw-content'], $client->get('legacy-download'));
        $client->post('invoices?finalize=true', ['title' => 'Rechnung']);
        $this->assertSame('finalize=true', $history[2]['request']->getUri()->getQuery());
    }

    private function clientWith(HandlerStack $handler): LexwareOffice
    {
        $client = new LexwareOffice('https://api.lexware.io', 'test-key', maxRequestsPerMinute: 0);
        $client->setClient(new Client([
            'base_uri' => 'https://api.lexware.io/v1/', 'handler' => $handler,
            'headers' => ['Authorization' => 'Bearer test-key', 'Accept' => 'application/json'],
        ]));

        return $client;
    }
}
