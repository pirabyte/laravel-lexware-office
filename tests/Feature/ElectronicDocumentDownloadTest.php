<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Pirabyte\LaravelLexwareOffice\Enums\ElectronicDocumentProfile;
use Pirabyte\LaravelLexwareOffice\Enums\FileFormat;
use Pirabyte\LaravelLexwareOffice\Exceptions\ElectronicDocumentNotAvailableException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ElectronicDocumentDownloadTest extends TestCase
{
    private array $history = [];

    #[DataProvider('resourcesAndProfiles')]
    public function test_xml_404_is_explained_by_the_profile(string $resource, string $endpoint, ElectronicDocumentProfile $profile): void
    {
        $client = $this->clientWith([
            new Response(404, [], '{"message":""}'),
            $this->voucher($resource, $profile),
        ]);

        try {
            $client->{$resource}()->file('voucher-id', FileFormat::XML);
            $this->fail('Expected download exception');
        } catch (LexwareOfficeApiException $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('/v1/'.$endpoint.'/voucher-id/file', $e->getRequestException()->getRequest()->getUri()->getPath());
            if ($profile === ElectronicDocumentProfile::XRECHNUNG) {
                $this->assertNotInstanceOf(ElectronicDocumentNotAvailableException::class, $e);
                $this->assertSame('Lexware Office API request failed (HTTP 404) for GET /v1/'.$endpoint.'/voucher-id/file', $e->getMessage());
            } else {
                $this->assertInstanceOf(ElectronicDocumentNotAvailableException::class, $e);
                $this->assertSame($profile, $e->profile);
                $this->assertProfileMessage($e, $profile);
            }
        }

        $this->assertCount(2, $this->history);
        $this->assertSame('application/xml', $this->history[0]['request']->getHeaderLine('Accept'));
        $this->assertSame('/v1/'.$endpoint.'/voucher-id', $this->history[1]['request']->getUri()->getPath());
    }

    #[DataProvider('resourcesAndProfiles')]
    public function test_electronic_document_selects_the_profile_format(string $resource, string $endpoint, ElectronicDocumentProfile $profile): void
    {
        $responses = [$this->voucher($resource, $profile)];
        if ($profile !== ElectronicDocumentProfile::NONE) {
            $responses[] = new Response(200, [], 'electronic-file');
        }
        $client = $this->clientWith($responses);

        if ($profile === ElectronicDocumentProfile::NONE) {
            try {
                $client->{$resource}()->electronicDocument('voucher-id');
                $this->fail('Expected missing electronic document');
            } catch (ElectronicDocumentNotAvailableException $e) {
                $this->assertSame($profile, $e->profile);
                $this->assertProfileMessage($e, $profile);
            }
            $this->assertCount(1, $this->history);
        } else {
            $file = $client->{$resource}()->electronicDocument('voucher-id');
            $this->assertSame('electronic-file', $file->stream->getContents());
            $this->assertCount(2, $this->history);
            $this->assertSame($profile === ElectronicDocumentProfile::XRECHNUNG ? 'application/xml' : 'application/pdf', $this->history[1]['request']->getHeaderLine('Accept'));
            $this->assertSame('/v1/'.$endpoint.'/voucher-id/file', $this->history[1]['request']->getUri()->getPath());
        }
        $this->assertSame('/v1/'.$endpoint.'/voucher-id', $this->history[0]['request']->getUri()->getPath());
    }

    #[DataProvider('failedLookups')]
    public function test_failed_profile_lookup_preserves_the_original_download_error(string $resource, string $endpoint, string $failure): void
    {
        $lookupFailure = match ($failure) {
            'connection' => new ConnectException('lookup-secret', new Request('GET', $endpoint.'/voucher-id')),
            'unreadable response' => new Response(200, [], '{}'),
            default => new Response((int) $failure, [], '{"message":"lookup-secret"}'),
        };
        $client = $this->clientWith([new Response(404, [], '{"message":""}'), $lookupFailure]);

        try {
            $client->{$resource}()->file('voucher-id', FileFormat::XML);
            $this->fail('Expected original download exception');
        } catch (LexwareOfficeApiException $e) {
            $this->assertNotInstanceOf(ElectronicDocumentNotAvailableException::class, $e);
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Lexware Office API request failed (HTTP 404) for GET /v1/'.$endpoint.'/voucher-id/file', $e->getMessage());
        }
        $this->assertCount(2, $this->history);
    }

    #[DataProvider('resources')]
    public function test_other_xml_errors_do_not_trigger_a_profile_lookup(string $resource, string $endpoint): void
    {
        $client = $this->clientWith([new Response(406, [], '{"message":"Unsupported format"}')]);

        try {
            $client->{$resource}()->file('voucher-id', FileFormat::XML);
            $this->fail('Expected original error');
        } catch (LexwareOfficeApiException $e) {
            $this->assertSame(406, $e->getStatusCode());
            $this->assertSame('Unsupported format', $e->getMessage());
        }
        $this->assertCount(1, $this->history);
    }

    #[DataProvider('resources')]
    public function test_electronic_document_propagates_its_initial_profile_lookup_error(string $resource, string $endpoint): void
    {
        $client = $this->clientWith([new Response(403, [], '{"message":"Access denied"}')]);

        try {
            $client->{$resource}()->electronicDocument('voucher-id');
            $this->fail('Expected profile lookup error');
        } catch (LexwareOfficeApiException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Access denied', $e->getMessage());
        }
        $this->assertCount(1, $this->history);
        $this->assertSame('/v1/'.$endpoint.'/voucher-id', $this->history[0]['request']->getUri()->getPath());
    }

    public static function resources(): array
    {
        return [['invoices', 'invoices'], ['creditNotes', 'credit-notes']];
    }

    public static function resourcesAndProfiles(): array
    {
        $cases = [];
        foreach (self::resources() as [$resource, $endpoint]) {
            foreach (ElectronicDocumentProfile::cases() as $profile) {
                $cases[$resource.' '.$profile->value] = [$resource, $endpoint, $profile];
            }
        }

        return $cases;
    }

    public static function failedLookups(): array
    {
        $cases = [];
        foreach (self::resources() as [$resource, $endpoint]) {
            foreach (['404', '429', '500', 'connection', 'unreadable response'] as $failure) {
                $cases[$resource.' '.$failure] = [$resource, $endpoint, $failure];
            }
        }

        return $cases;
    }

    private function assertProfileMessage(ElectronicDocumentNotAvailableException $exception, ElectronicDocumentProfile $profile): void
    {
        $expected = $profile === ElectronicDocumentProfile::EN16931
            ? 'ZUGFeRD documents (EN16931) do not have a separate XML file. The XML is embedded in the PDF; download the file in the default or PDF format.'
            : 'This document has no electronic format (profile NONE), only a PDF. Check that e-invoicing is enabled in Lexware, the recipient is a company contact, and the company details including bank details are complete.';
        $this->assertSame($expected, $exception->getMessage());
    }

    private function voucher(string $resource, ElectronicDocumentProfile $profile): Response
    {
        $fixture = $resource === 'invoices' ? 'invoice' : 'credit-note';
        $data = json_decode(file_get_contents(__DIR__.'/../Fixtures/invoicing/'.$fixture.'.json'), true);
        $data['electronicDocumentProfile'] = $profile->value;

        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function clientWith(array $responses): LexwareOffice
    {
        $this->history = [];
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $client = new LexwareOffice('https://api.lexware.io', 'test-key', maxRequestsPerMinute: 0);
        $client->setClient(new Client(['base_uri' => 'https://api.lexware.io/v1/', 'handler' => $handler]));

        return $client;
    }
}
