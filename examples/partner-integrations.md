# Partner Integrations Example

This example demonstrates how to work with Partner Integrations in the Lexware Office API. Partner Integrations allow you to store and retrieve integration-specific data for a lexoffice organization.

## Prerequisites

- You need to have configured the Laravel Lexware Office package as described in the main README
- Your API key needs to have the required OAuth scopes for Partner Integrations: `partnerintegrations.write` and `partnerintegrations.read`

## Getting Partner Integration Data

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// Get partner integration data
$partnerIntegration = LexwareOffice::partnerIntegrations()->get();

// Access specific data
$partnerId = $partnerIntegration->get('partnerId');
$customerNumber = $partnerIntegration->get('customerNumber');
$externalId = $partnerIntegration->get('externalId');

// Access custom data
$customData = $partnerIntegration->get('data', []);
$value1 = $customData['additionalData1'] ?? null;
```

## Updating Partner Integration Data

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\PartnerIntegration;

// Option 1: Get existing data and update it
$partnerIntegration = LexwareOffice::partnerIntegrations()->get();
$partnerIntegration->set('externalId', 'new-external-id');
$partnerIntegration->set('data', [
    'additionalData1' => 'updatedValue1',
    'additionalData2' => 'updatedValue2',
    'newData' => 'newValue'
]);

// Save the updated data
$updatedPartnerIntegration = LexwareOffice::partnerIntegrations()->update($partnerIntegration);

// Option 2: Create a new PartnerIntegration object from scratch
$partnerIntegration = new PartnerIntegration();
$partnerIntegration->setData([
    'partnerId' => 'your-partner-id',
    'customerNumber' => 'your-customer-number',
    'externalId' => 'your-external-id',
    'data' => [
        'additionalData1' => 'value1',
        'additionalData2' => 'value2'
    ]
]);

// Save the new data
$updatedPartnerIntegration = LexwareOffice::partnerIntegrations()->update($partnerIntegration);
```

## Error Handling

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

try {
    $partnerIntegration = LexwareOffice::partnerIntegrations()->get();
    // Process the data...
} catch (LexwareOfficeApiException $e) {
    // Handle API errors
    $statusCode = $e->getCode();
    $message = $e->getMessage();
    
    if ($statusCode === 401) {
        // Unauthorized - invalid API key or missing required scopes
        // Handle accordingly...
    } elseif ($statusCode === 429) {
        // Rate limit exceeded
        // Handle accordingly...
    } else {
        // Other errors
        // Handle accordingly...
    }
}
```

## Partner Integration Data Structure

The Partner Integration data structure is flexible but typically includes:

- `partnerId`: Your partner ID in lexoffice
- `customerNumber`: Customer number in your system
- `externalId`: External identifier for the integration
- `data`: Custom data object for storing integration-specific information

The specific fields available might depend on your partner agreement with lexoffice.