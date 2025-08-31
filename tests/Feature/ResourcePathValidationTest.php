<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ResourcePathValidationTest extends TestCase
{
    /** @test */
    public function it_validates_all_resource_paths_do_not_start_with_slash(): void
    {
        echo "\n=== VALIDIERUNG ALLER RESSOURCEN-PFADE ===\n";
        
        $accessToken = 'test-token';
        config(['lexware-office.base_url' => 'https://api.lexoffice.io']);
        
        $capturedRequests = [];
        
        // Add responses for all possible requests
        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = new Response(200, ['Content-Type' => 'application/json'], '[]');
        }
        
        // Mock Handler der alle Requests erfasst
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        
        $handlerStack->push(function (callable $handler) use (&$capturedRequests) {
            return function (Request $request, array $options) use ($handler, &$capturedRequests) {
                $capturedRequests[] = [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'path' => $request->getUri()->getPath(),
                    'resource' => $this->identifyResource($request),
                ];
                
                // Call the handler to maintain the promise chain
                return $handler($request, $options);
            };
        });
        
        $client = LexwareOfficeFactory::withApiKey($accessToken, config('lexware-office.base_url'));
        
        // Hole Original-Konfiguration
        $originalClient = $client->client();
        $baseUri = $originalClient->getConfig('base_uri');
        $headers = $originalClient->getConfig('headers');
        
        // Setze Mock Client
        $mockClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => $baseUri,
            'headers' => $headers,
        ]);
        $client->setClient($mockClient);
        
        echo "Base URI: {$baseUri}\n\n";
        
        // Teste alle Ressourcen
        $this->testFinancialAccountResource($client);
        $this->testContactResource($client);
        $this->testVoucherResource($client);
        $this->testFinancialTransactionResource($client);
        $this->testProfileResource($client);
        $this->testPostingCategoryResource($client);
        $this->testCountryResource($client);
        $this->testPartnerIntegrationResource($client);
        
        echo "\n=== ANALYSE DER ERFASSTEN REQUESTS ===\n";
        
        $validPaths = [];
        $invalidPaths = [];
        
        foreach ($capturedRequests as $request) {
            $path = $request['path'];
            $resource = $request['resource'];
            
            echo "Resource: {$resource}\n";
            echo "  Method: {$request['method']}\n";
            echo "  URI: {$request['uri']}\n";
            echo "  Path: {$path}\n";
            
            // Prüfe ob Path korrekt mit /v1/ beginnt
            if (str_starts_with($path, '/v1/')) {
                echo "  ✅ Korrekt: Path beginnt mit /v1/\n";
                $validPaths[] = $resource;
            } else {
                echo "  ❌ FEHLER: Path beginnt NICHT mit /v1/\n";
                echo "    Erwartet: /v1/...\n";
                echo "    Tatsächlich: {$path}\n";
                $invalidPaths[] = $resource;
            }
            echo "\n";
        }
        
        echo "=== ZUSAMMENFASSUNG ===\n";
        echo "Gültige Pfade: " . count($validPaths) . "\n";
        echo "Ungültige Pfade: " . count($invalidPaths) . "\n";
        
        if (!empty($invalidPaths)) {
            echo "\n❌ FEHLERHAFTE RESSOURCEN:\n";
            foreach ($invalidPaths as $resource) {
                echo "  - {$resource}\n";
            }
            echo "\nDiese Ressourcen müssen korrigiert werden!\n";
        } else {
            echo "\n✅ Alle Ressourcen verwenden korrekte Pfade!\n";
        }
        
        // Assertions
        $this->assertEmpty($invalidPaths, 'Alle Ressourcen sollten Pfade ohne führenden / verwenden');
        $this->assertNotEmpty($validPaths, 'Es sollten gültige Pfade gefunden werden');
    }
    
    private function identifyResource(Request $request): string
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        
        if (str_contains($path, 'finance/accounts') || str_contains($path, 'financial-accounts')) {
            return 'FinancialAccountResource';
        }
        if (str_contains($path, 'contacts')) {
            return 'ContactResource';
        }
        if (str_contains($path, 'vouchers')) {
            return 'VoucherResource';
        }
        if (str_contains($path, 'finance/transactions')) {
            return 'FinancialTransactionResource';
        }
        if (str_contains($path, 'profile')) {
            return 'ProfileResource';
        }
        if (str_contains($path, 'posting-categories')) {
            return 'PostingCategoryResource';
        }
        if (str_contains($path, 'countries')) {
            return 'CountryResource';
        }
        if (str_contains($path, 'partner-integrations')) {
            return 'PartnerIntegrationResource';
        }
        
        return 'UnknownResource';
    }
    
    private function testFinancialAccountResource($client): void
    {
        echo "--- Testing FinancialAccountResource ---\n";
        
        try {
            // Test filter method
            $client->financialAccounts()->filter(['externalReference' => 'test']);
        } catch (\Exception $e) {
            // Ignore exceptions, we just want to capture requests
        }
        
        try {
            // Test get method
            $client->financialAccounts()->get('test-id');
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testContactResource($client): void
    {
        echo "--- Testing ContactResource ---\n";
        
        try {
            $client->contacts()->all();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
        
        try {
            $client->contacts()->get('test-id');
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testVoucherResource($client): void
    {
        echo "--- Testing VoucherResource ---\n";
        
        try {
            $client->vouchers()->all();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
        
        try {
            $client->vouchers()->get('test-id');
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testFinancialTransactionResource($client): void
    {
        echo "--- Testing FinancialTransactionResource ---\n";
        
        try {
            $client->financialTransactions()->get('test-id');
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testProfileResource($client): void
    {
        echo "--- Testing ProfileResource ---\n";
        
        try {
            $client->profile()->get();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testPostingCategoryResource($client): void
    {
        echo "--- Testing PostingCategoryResource ---\n";
        
        try {
            $client->postingCategories()->get();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testCountryResource($client): void
    {
        echo "--- Testing CountryResource ---\n";
        
        try {
            $client->countries()->all();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
    
    private function testPartnerIntegrationResource($client): void
    {
        echo "--- Testing PartnerIntegrationResource ---\n";
        
        try {
            $client->partnerIntegrations()->get();
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }
}
