<?php

namespace Tests\Live;

use PHPUnit\Framework\TestCase;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Pirabyte\LaravelLexwareOffice\Models\VoucherItem;
use GuzzleHttp\Psr7\Stream;

/**
 * Live API Tests - Tests against the actual Lexware Office API
 * 
 * IMPORTANT: These tests run against the live API and require valid credentials.
 * Set the following environment variables before running:
 * - LEXWARE_API_KEY: Your API key
 * - LEXWARE_BASE_URL: Base URL (default: https://api.lexoffice.io)
 * 
 * Run with: vendor/bin/phpunit tests/Live/LiveApiTest.php --group=live
 */
class LiveApiTest extends TestCase
{
    private LexwareOffice $client;
    private bool $skipTests = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip tests if no API key is provided
        $apiKey = $_ENV['LEXWARE_API_KEY'] ?? getenv('LEXWARE_API_KEY');
        if (empty($apiKey)) {
            $this->skipTests = true;
            $this->markTestSkipped('LEXWARE_API_KEY environment variable not set. Set it to run live API tests.');
            return;
        }

        $baseUrl = $_ENV['LEXWARE_BASE_URL'] ?? getenv('LEXWARE_BASE_URL') ?? 'https://api.lexoffice.io';

        $this->client = new LexwareOffice($apiKey, $baseUrl);
    }

    /**
     * @group live
     */
    public function test_profile_endpoint()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        $profile = $this->client->profile()->get();

        $this->assertNotEmpty($profile->getCompanyName());
        $this->assertNotEmpty($profile->getId());
        
        echo "\n✅ Profile Test Passed";
        echo "\n   Company: " . $profile->getCompanyName();
        echo "\n   Profile ID: " . $profile->getId();
    }

    /**
     * @group live
     */
    public function test_countries_endpoint()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        $countries = $this->client->countries()->all();

        $this->assertIsArray($countries);
        $this->assertNotEmpty($countries);
        
        // Find Germany
        $germany = array_filter($countries, fn($country) => $country->getCountryCode() === 'DE');
        $this->assertNotEmpty($germany);
        
        echo "\n✅ Countries Test Passed";
        echo "\n   Total countries: " . count($countries);
        echo "\n   Germany found: " . (empty($germany) ? 'No' : 'Yes');
    }

    /**
     * @group live
     */
    public function test_posting_categories_endpoint()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        $categories = $this->client->postingCategories()->all();

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
        
        echo "\n✅ Posting Categories Test Passed";
        echo "\n   Total categories: " . count($categories);
    }

    /**
     * @group live
     */
    public function test_financial_accounts_endpoint()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        $accounts = $this->client->financialAccounts()->all();

        $this->assertIsArray($accounts);
        $this->assertNotEmpty($accounts);
        
        echo "\n✅ Financial Accounts Test Passed";
        echo "\n   Total accounts: " . count($accounts);
    }

    /**
     * @group live
     */
    public function test_contacts_crud_operations()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        // Create a test contact
        $contact = new Contact();
        $contact->setVersion(0)
            ->setPerson([
                'salutation' => 'Herr',
                'firstName' => 'Max',
                'lastName' => 'Mustermann Test API ' . time(),
            ])
            ->setAddresses([
                'billing' => [
                    [
                        'street' => 'Teststraße 123',
                        'zip' => '12345',
                        'city' => 'Teststadt',
                        'countryCode' => 'DE',
                    ],
                ],
            ])
            ->setEmailAddresses([
                'business' => ['test' . time() . '@example.com'],
            ]);

        // Create contact
        $createdContact = $this->client->contacts()->create($contact);
        $this->assertNotEmpty($createdContact->getId());
        
        echo "\n✅ Contact Create Test Passed";
        echo "\n   Created contact ID: " . $createdContact->getId();

        // Read contact
        $retrievedContact = $this->client->contacts()->get($createdContact->getId());
        $this->assertEquals($createdContact->getId(), $retrievedContact->getId());
        
        echo "\n✅ Contact Read Test Passed";

        // Update contact
        $retrievedContact->getPerson()['lastName'] = 'Updated Test API ' . time();
        $updatedContact = $this->client->contacts()->update($retrievedContact->getId(), $retrievedContact);
        $this->assertStringContains('Updated Test API', $updatedContact->getPerson()['lastName']);
        
        echo "\n✅ Contact Update Test Passed";

        return $updatedContact->getId(); // Return for cleanup in other tests
    }

    /**
     * @group live
     */
    public function test_voucher_operations()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        // Get all vouchers (limited)
        $vouchersResponse = $this->client->vouchers()->all(0, 5);
        
        $this->assertIsArray($vouchersResponse);
        $this->assertArrayHasKey('content', $vouchersResponse);
        $this->assertArrayHasKey('pagination', $vouchersResponse);
        
        echo "\n✅ Vouchers List Test Passed";
        echo "\n   Total vouchers in response: " . count($vouchersResponse['content']);
        echo "\n   Total elements: " . $vouchersResponse['pagination']['totalElements'];

        // If we have vouchers, test getting one
        if (!empty($vouchersResponse['content'])) {
            $firstVoucher = $vouchersResponse['content'][0];
            $retrievedVoucher = $this->client->vouchers()->get($firstVoucher->getId());
            
            $this->assertEquals($firstVoucher->getId(), $retrievedVoucher->getId());
            
            echo "\n✅ Voucher Get Test Passed";
            echo "\n   Retrieved voucher ID: " . $retrievedVoucher->getId();
        }
    }

    /**
     * @group live
     * @group file-upload
     */
    public function test_voucher_file_upload()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        // Get a voucher to attach file to
        $vouchersResponse = $this->client->vouchers()->all(0, 1);
        
        if (empty($vouchersResponse['content'])) {
            $this->markTestSkipped('No vouchers available for file upload test');
        }

        $voucher = $vouchersResponse['content'][0];
        
        // Create a test PDF content
        $testPdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
100 700 Td
(Test PDF for API) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000206 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
299
%%EOF';

        // Create stream from content
        $stream = new Stream(fopen('data://text/plain,' . $testPdfContent, 'r'));
        
        try {
            $result = $this->client->vouchers()->attachFile(
                $voucher->getId(),
                $stream,
                'test-api-upload-' . time() . '.pdf',
                'voucher'
            );

            $this->assertIsArray($result);
            
            echo "\n✅ Voucher File Upload Test Passed";
            echo "\n   Attached to voucher ID: " . $voucher->getId();
            if (isset($result['id'])) {
                echo "\n   File ID: " . $result['id'];
            }
            
        } catch (\Exception $e) {
            echo "\n❌ File Upload Test Failed: " . $e->getMessage();
            throw $e;
        }
    }

    /**
     * @group live
     */
    public function test_financial_transactions()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        $transactions = $this->client->financialTransactions()->all(0, 5);
        
        $this->assertIsArray($transactions);
        $this->assertArrayHasKey('content', $transactions);
        $this->assertArrayHasKey('pagination', $transactions);
        
        echo "\n✅ Financial Transactions Test Passed";
        echo "\n   Total transactions in response: " . count($transactions['content']);
    }

    /**
     * @group live
     */
    public function test_partner_integrations()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        try {
            $integrations = $this->client->partnerIntegrations()->all();
            
            $this->assertIsArray($integrations);
            
            echo "\n✅ Partner Integrations Test Passed";
            echo "\n   Total integrations: " . count($integrations);
            
        } catch (\Exception $e) {
            // Some API keys might not have access to partner integrations
            echo "\n⚠️  Partner Integrations Test Skipped: " . $e->getMessage();
            $this->markTestSkipped('Partner integrations endpoint not accessible with current API key');
        }
    }

    /**
     * @group live
     */
    public function test_error_handling()
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Skipping live API tests');
        }

        try {
            // Try to get a non-existent contact
            $this->client->contacts()->get('non-existent-id-12345');
            $this->fail('Expected exception for non-existent contact');
        } catch (\Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException $e) {
            $this->assertNotEmpty($e->getMessage());
            echo "\n✅ Error Handling Test Passed";
            echo "\n   Correctly handled 404 error for non-existent contact";
        }
    }

    /**
     * Print test summary
     */
    public static function tearDownAfterClass(): void
    {
        echo "\n\n" . str_repeat("=", 60);
        echo "\n🎯 LIVE API TEST SUMMARY";
        echo "\n" . str_repeat("=", 60);
        echo "\n";
        echo "\nTo run these tests with your API credentials:";
        echo "\n1. Set environment variable: export LEXWARE_API_KEY='your-api-key'";
        echo "\n2. Run: vendor/bin/phpunit tests/Live/LiveApiTest.php --group=live";
        echo "\n3. For file upload tests: vendor/bin/phpunit tests/Live/LiveApiTest.php --group=file-upload";
        echo "\n";
    }
}