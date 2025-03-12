# Lexware Office API - Contact Examples

This document shows various examples of how to create and retrieve contacts using the Lexware Office API client.

## Important API Limitations

**Email Addresses:**
- The API supports a maximum of ONE email address per type (business, office, private, other)
- It's possible to retrieve contacts with multiple emails of the same type, but when updating such contacts, some data might be lost

**Phone Numbers:**
- The API supports a maximum of ONE phone number per type (business, office, mobile, private, fax, other)
- It's possible to retrieve contacts with multiple phone numbers of the same type, but when updating such contacts, some data might be lost

## Creating a Simple Person Contact

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;

// Create a person contact with basic information
$contact = Contact::createPerson('Max', 'Mustermann', 'Herr');

// Add a customer role
$contact->setAsCustomer(['number' => 'K-12345']);

// Save the contact
$savedContact = LexwareOffice::contacts()->create($contact);

// Get the contact ID
$contactId = $savedContact->getId();
```

## Creating a Person Contact with Complete Data

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;

// Create a person contact
$contact = Contact::createPerson('Inge', 'Musterfrau', 'Frau');

// Add customer role and additional information
$contact->setAsCustomer()
    ->setNote('Wichtiger Kunde')
    ->addAddress([
        'street' => 'Musterstraße 1',
        'zip' => '12345',
        'city' => 'Musterstadt',
        'countryCode' => 'DE'  // Required - ISO 3166 alpha2 country code
    ])
    ->addEmailAddress('inge@example.com', 'business')
    ->addPhoneNumber('+49123456789', 'business');

// Save the contact
$savedContact = LexwareOffice::contacts()->create($contact);
```

## Creating a Company Contact

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;

// Create a company contact
$contact = Contact::createCompany('Musterfirma GmbH');

// Add vendor role and address
$contact->setAsVendor(['number' => 'L-789'])
    ->addAddress([
        'street' => 'Industriestraße 42',
        'zip' => '54321',
        'city' => 'Musterstadt',
        'countryCode' => 'DE'
    ]);

// Save the contact
$savedContact = LexwareOffice::contacts()->create($contact);
```

## Retrieving a Contact

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// Get a contact by its ID
$contact = LexwareOffice::contacts()->get('contact-id-here');

// Access contact information
if ($contact->getPerson()) {
    // It's a person contact
    $firstName = $contact->getPerson()->getFirstName();
    $lastName = $contact->getPerson()->getLastName();
} else if ($contact->getCompany()) {
    // It's a company contact
    $companyName = $contact->getCompany()->getName();
}

// Check roles
$roles = $contact->getRoles();
$isCustomer = isset($roles['customer']);
$isVendor = isset($roles['vendor']);

// Access addresses and contact details
$addresses = $contact->getAddresses();
$emailAddresses = $contact->getEmailAddresses();
$phoneNumbers = $contact->getPhoneNumbers();

// Access specific email addresses by type
$businessEmail = $contact->getEmailAddress('business');
$privateEmail = $contact->getEmailAddress('private');
$officeEmail = $contact->getEmailAddress('office');
$otherEmail = $contact->getEmailAddress('other');

// Access specific phone numbers by type
$businessPhone = $contact->getPhoneNumber('business');
$mobilePhone = $contact->getPhoneNumber('mobile');
$faxNumber = $contact->getPhoneNumber('fax');
$privatePhone = $contact->getPhoneNumber('private');
$officePhone = $contact->getPhoneNumber('office');
$otherPhone = $contact->getPhoneNumber('other');

// Using multiple types of emails and phone numbers
$contact = Contact::createPerson('Hans', 'Schmidt', 'Herr');
$contact->setAsCustomer()
    // Add various email types
    ->addEmailAddress('hans.business@example.com', 'business')
    ->addEmailAddress('hans.private@example.com', 'private') 
    ->addEmailAddress('hans.office@example.com', 'office')
    
    // Add various phone types
    ->addPhoneNumber('+4912345678901', 'business')
    ->addPhoneNumber('+4915123456789', 'mobile')
    ->addPhoneNumber('+49123456789999', 'fax');
```

## Filtering Contacts

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// Search for customers only
$customers = LexwareOffice::contacts()->filter(['customer' => true]);

// Search for vendors only
$vendors = LexwareOffice::contacts()->filter(['vendor' => true]);

// Search by name (works for both person and company contacts)
$contactsByName = LexwareOffice::contacts()->filter(['name' => 'Muster']);

// Search by email
$contactsByEmail = LexwareOffice::contacts()->filter(['email' => 'example.com']);

// Search by customer/vendor number
$contactsByNumber = LexwareOffice::contacts()->filter(['number' => 'K-12345']);

// Pagination
$page2 = LexwareOffice::contacts()->filter([
    'customer' => true,
    'page' => 1,  // 0-based page index
    'size' => 25  // items per page (max 250)
]);

// Get total count of contacts
$totalContacts = LexwareOffice::contacts()->count();
```

## Working with Multiple Contacts Efficiently

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// Use the auto-paging iterator to process all contacts
$iterator = LexwareOffice::contacts()->getAutoPagingIterator(['customer' => true]);

foreach ($iterator as $contact) {
    // Process each contact without manual pagination
    // This automatically fetches more pages as needed
}
```