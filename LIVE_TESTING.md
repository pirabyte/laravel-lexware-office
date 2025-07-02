# Live API Testing Guide

This guide explains how to test the Laravel Lexware Office package against the live Lexware Office API.

## ⚠️ Important Notes

- **Live API**: These tests run against the real Lexware Office API
- **API Limits**: Respect the 2 requests/second rate limit
- **Test Data**: Some tests create/modify data in your Lexware Office account
- **Credentials**: Never commit your API key to version control

## 🚀 Quick Start

### 1. Get Your API Key

1. Log into your Lexware Office account
2. Go to [Public API Management](https://app.lexoffice.de/addons/public-api)
3. Generate or copy your API key

### 2. Setup Environment

**Option A: Using the setup script**
```bash
./live-test-setup.sh 'your-api-key-here'
```

**Option B: Manual setup**
```bash
export LEXWARE_API_KEY='your-api-key-here'
export LEXWARE_BASE_URL='https://api.lexoffice.io'
```

**Option C: Using .env file**
```bash
cp .env.live-testing.example .env.live-testing
# Edit .env.live-testing with your credentials
source .env.live-testing
```

### 3. Run Tests

```bash
# Test all endpoints
vendor/bin/phpunit tests/Live/LiveApiTest.php --group=live

# Test file upload functionality
vendor/bin/phpunit tests/Live/LiveApiTest.php --group=file-upload

# Test specific functionality
vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_contacts_crud_operations
```

## 📋 Available Test Suites

### Core API Tests
- **Profile**: `test_profile_endpoint` - Tests basic connectivity and profile info
- **Countries**: `test_countries_endpoint` - Lists available countries
- **Posting Categories**: `test_posting_categories_endpoint` - Lists posting categories
- **Financial Accounts**: `test_financial_accounts_endpoint` - Lists financial accounts

### CRUD Operations
- **Contacts**: `test_contacts_crud_operations` - Create, read, update contact
- **Vouchers**: `test_voucher_operations` - List and retrieve vouchers
- **Financial Transactions**: `test_financial_transactions` - List transactions

### File Operations
- **File Upload**: `test_voucher_file_upload` - Upload files to vouchers

### Error Handling
- **Error Handling**: `test_error_handling` - Tests API error responses

## 🎯 Test Examples

### Test Basic Connectivity
```bash
vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_profile_endpoint
```

### Test Contact Operations
```bash
vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_contacts_crud_operations
```

### Test File Upload
```bash
vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_voucher_file_upload
```

### Run All Tests with Verbose Output
```bash
vendor/bin/phpunit tests/Live/LiveApiTest.php --group=live --testdox
```

## 🔧 Troubleshooting

### "LEXWARE_API_KEY environment variable not set"
Make sure you've exported the environment variable:
```bash
export LEXWARE_API_KEY='your-api-key-here'
```

### "API Connection Failed"
Check:
1. Your API key is correct and active
2. You have internet connectivity  
3. Your API key has required permissions
4. Rate limits aren't exceeded (max 2 requests/second)

### "Partner integrations endpoint not accessible"
Some API keys don't have access to partner integrations. This is normal and the test will be skipped.

### "No vouchers available for file upload test"
Create at least one voucher in your Lexware Office account, or the file upload test will be skipped.

## 📊 Test Output Example

```
✅ Profile Test Passed
   Company: Your Company Name
   Profile ID: 12345678-1234-1234-1234-123456789012

✅ Countries Test Passed
   Total countries: 249
   Germany found: Yes

✅ Contact Create Test Passed
   Created contact ID: 87654321-4321-4321-4321-210987654321

✅ Voucher File Upload Test Passed
   Attached to voucher ID: 11111111-2222-3333-4444-555555555555
   File ID: 99999999-8888-7777-6666-555555555555
```

## 🔒 Security

- Never commit your API key to version control
- Use environment variables or local config files
- The `.env.live-testing` file is git-ignored for your safety
- Rotate your API key regularly

## 🚫 What NOT to Test

- Don't run these tests in CI/CD pipelines
- Don't use production data for testing
- Don't exceed rate limits (2 requests/second)
- Don't commit test data permanently

## 📝 Adding New Tests

To add new live API tests:

1. Add your test method to `tests/Live/LiveApiTest.php`
2. Use the `@group live` annotation
3. Add the skip check: `if ($this->skipTests) { $this->markTestSkipped('Skipping live API tests'); }`
4. Use `$this->client` to access the API
5. Add helpful echo statements for test output
6. Handle expected errors gracefully

Example:
```php
/**
 * @group live
 */
public function test_my_new_feature()
{
    if ($this->skipTests) {
        $this->markTestSkipped('Skipping live API tests');
    }

    $result = $this->client->myResource()->myMethod();
    
    $this->assertNotEmpty($result);
    echo "\n✅ My New Feature Test Passed";
}
```