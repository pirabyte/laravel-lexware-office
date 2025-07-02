<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Traits\SupportsOptimisticLocking;

class Contact implements \JsonSerializable
{
    use SupportsOptimisticLocking;
    private ?string $id = null;

    private ?string $organizationId = null;

    private int $version = 0;

    private array $roles = [];

    private ?Person $person = null;

    private ?Company $company = null;

    private array $addresses = [];

    private array $emailAddresses = [];

    private array $phoneNumbers = [];

    private ?XRechnung $xRechnung = null;

    private ?string $note = null;

    private bool $archived = false;

    private ?string $createdDate = null;

    private ?string $updatedDate = null;

    /**
     * Creates a new contact with a person as the contact type
     *
     * @return static
     */
    public static function createPerson(?string $firstName, string $lastName, ?string $salutation = null): self
    {
        $person = new Person();
        $person->setLastName($lastName);

        if ($firstName !== null) {
            $person->setFirstName($firstName);
        }

        if ($salutation !== null) {
            $person->setSalutation($salutation);
        }

        $contact = new self();
        $contact->setPerson($person);
        $contact->setVersion(0);

        return $contact;
    }

    /**
     * Creates a new contact with a company as the contact type
     *
     * @param  string  $name  Company name
     * @return static
     */
    public static function createCompany(string $name): self
    {
        $company = new Company();
        $company->setName($name);

        $contact = new self();
        $contact->setCompany($company);
        $contact->setVersion(0);

        return $contact;
    }

    // Getters und Setters für alle neuen Felder

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function setAddresses(array $addresses): self
    {
        $processedAddresses = [];
        foreach ($addresses as $type => $addressCollection) {
            foreach ($addressCollection as $addressData) {
                if ($addressData instanceof Address) {
                    $processedAddresses[$type][] = $addressData;
                } else {
                    $processedAddresses[$type][] = Address::fromArray($addressData);
                }
            }
        }
        $this->addresses = $processedAddresses;

        return $this;
    }

    public function getEmailAddresses(): array
    {
        return $this->emailAddresses;
    }

    /**
     * Gets an email address of a specific type
     *
     * @param  string  $type  The type of email address (business, office, private, other)
     * @return string|null The email address or null if not found
     */
    public function getEmailAddress(string $type): ?string
    {
        return $this->emailAddresses[$type][0] ?? null;
    }

    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    /**
     * Gets a phone number of a specific type
     *
     * @param  string  $type  The type of phone number (business, office, mobile, private, fax, other)
     * @return string|null The phone number or null if not found
     */
    public function getPhoneNumber(string $type): ?string
    {
        return $this->phoneNumbers[$type][0] ?? null;
    }

    public function getXRechnung(): ?XRechnung
    {
        return $this->xRechnung;
    }

    public function setXRechnung(?XRechnung $xRechnung): self
    {
        $this->xRechnung = $xRechnung;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    // Factory-Methode aktualisieren
    public static function fromArray(array $data): self
    {
        $contact = new self();

        if (isset($data['id'])) {
            $contact->setId($data['id']);
        }

        if (isset($data['organizationId'])) {
            $contact->setOrganizationId($data['organizationId']);
        }

        if (isset($data['version'])) {
            $contact->setVersion($data['version']);
        }

        if (isset($data['roles'])) {
            $contact->setRoles($data['roles']);
        }

        if (isset($data['person'])) {
            if ($data['person'] instanceof Person) {
                $contact->setPerson($data['person']);
            } else {
                $contact->setPerson(Person::fromArray($data['person']));
            }
        }

        if (isset($data['company'])) {
            if ($data['company'] instanceof Company) {
                $contact->setCompany($data['company']);
            } else {
                $contact->setCompany(Company::fromArray($data['company']));
            }
        }

        if (isset($data['addresses'])) {
            $contact->setAddresses($data['addresses']);
        }

        if (isset($data['emailAddresses'])) {
            $contact->setEmailAddresses($data['emailAddresses']);
        }

        if (isset($data['phoneNumbers'])) {
            $contact->setPhoneNumbers($data['phoneNumbers']);
        }

        if (isset($data['xRechnung'])) {
            if ($data['xRechnung'] instanceof XRechnung) {
                $contact->setXRechnung($data['xRechnung']);
            } else {
                $contact->setXRechnung(XRechnung::fromArray($data['xRechnung']));
            }
        }

        if (isset($data['note'])) {
            $contact->setNote($data['note']);
        }

        if (isset($data['archived'])) {
            $contact->setArchived($data['archived']);
        }

        if (isset($data['createdDate'])) {
            $contact->setCreatedDate($data['createdDate']);
        }

        if (isset($data['updatedDate'])) {
            $contact->setUpdatedDate($data['updatedDate']);
        }

        return $contact;
    }

    // jsonSerialize aktualisieren
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'organizationId' => $this->organizationId,
            'version' => $this->version,
            'roles' => $this->roles,
        ];

        if ($this->person) {
            $data['person'] = $this->person->jsonSerialize();
        }

        if ($this->company) {
            $data['company'] = $this->company->jsonSerialize();
        }

        if (! empty($this->addresses)) {
            $serializedAddresses = [];
            foreach ($this->addresses as $type => $addressCollection) {
                foreach ($addressCollection as $addressObject) {
                    $serializedAddresses[$type][] = $addressObject->jsonSerialize();
                }
            }
            $data['addresses'] = $serializedAddresses;
        }

        if ($this->xRechnung) {
            $data['xRechnung'] = $this->xRechnung->jsonSerialize();
        }

        if (! empty($this->emailAddresses)) {
            $data['emailAddresses'] = $this->emailAddresses;
        }

        if (! empty($this->phoneNumbers)) {
            $data['phoneNumbers'] = $this->phoneNumbers;
        }

        if ($this->note) {
            $data['note'] = $this->note;
        }

        $data['archived'] = $this->archived;

        return $data;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Adds a role to the contact
     *
     * @param  string  $roleType  One of: customer, vendor
     * @return $this
     */
    public function addRole(string $roleType): self
    {
        $this->roles[$roleType] = null;

        return $this;
    }

    /**
     * Sets the contact as a customer
     *
     * @param  array  $roleData  Optional customer data
     * @return $this
     */
    public function setAsCustomer(): self
    {
        return $this->addRole('customer');
    }

    /**
     * Sets the contact as a vendor
     *
     * @return $this
     */
    public function setAsVendor(): self
    {
        return $this->addRole('vendor');
    }

    /**
     * Adds an address to the contact
     *
     * @param  array  $addressData  Address data with the following format:
     *                              [
     *                              'street' => 'Musterstraße 1',
     *                              'zip' => '12345',
     *                              'city' => 'Musterstadt',
     *                              'countryCode' => 'DE',
     *                              'supplement' => 'Optional address supplement'
     *                              ]
     * @param  string  $type  The address type ('billing' or 'shipping')
     * @return $this
     */
    public function addAddress(array $addressData, string $type = 'billing'): self
    {
        // Validate address type
        $validTypes = ['billing', 'shipping'];
        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid address type: $type. Must be one of: ".implode(', ', $validTypes));
        }

        // Replace existing address of this type or add new one
        $this->addresses[$type] = [Address::fromArray($addressData)];

        return $this;
    }

    /**
     * Gets an address of a specific type
     *
     * @param  string  $type  The type of address ('billing' or 'shipping')
     * @return array|null The address data or null if not found
     */
    public function getAddress(string $type): ?Address
    {
        return $this->addresses[$type][0] ?? null;
    }

    /**
     * Adds a billing address to the contact
     *
     * @param  string  $street  Street name and number
     * @param  string  $zip  Postal code
     * @param  string  $city  City
     * @param  string  $countryCode  ISO 3166 alpha2 country code (e.g. 'DE')
     * @param  string|null  $supplement  Optional address supplement
     * @return $this
     */
    public function addBillingAddress(string $street, string $zip, string $city, string $countryCode, ?string $supplement = null): self
    {
        $addressData = [
            'street' => $street,
            'zip' => $zip,
            'city' => $city,
            'countryCode' => $countryCode,
        ];

        if ($supplement !== null) {
            $addressData['supplement'] = $supplement;
        }

        return $this->addAddress($addressData, 'billing');
    }

    /**
     * Adds a shipping address to the contact
     *
     * @param  string  $street  Street name and number
     * @param  string  $zip  Postal code
     * @param  string  $city  City
     * @param  string  $countryCode  ISO 3166 alpha2 country code (e.g. 'DE')
     * @param  string|null  $supplement  Optional address supplement
     * @return $this
     */
    public function addShippingAddress(string $street, string $zip, string $city, string $countryCode, ?string $supplement = null): self
    {
        $addressData = [
            'street' => $street,
            'zip' => $zip,
            'city' => $city,
            'countryCode' => $countryCode,
        ];

        if ($supplement !== null) {
            $addressData['supplement'] = $supplement;
        }

        return $this->addAddress($addressData, 'shipping');
    }

    /**
     * Sets the email addresses for the contact
     *
     * Note: The API supports only one email address per type (business, office, private, other)
     *
     * @param  array  $emailAddresses  Array with email addresses in API format
     * @return $this
     */
    public function setEmailAddresses(array $emailAddresses): self
    {
        $processedEmails = [];
        foreach ($emailAddresses as $key => $value) {
            if (is_array($value)) {
                $processedEmails[$key] = $value;
            } else {
                $processedEmails[$key] = [$value];
            }
        }
        $this->emailAddresses = $processedEmails;

        return $this;
    }

    /**
     * Adds an email address to the contact
     *
     * Note: The API supports only one email address per type (business, office, private, other)
     *
     * @param  string  $email  The email address
     * @param  string  $type  The type of email address (business, office, private, other)
     * @return $this
     */
    public function addEmailAddress(string $email, string $type = 'business'): self
    {
        // Validate email type
        $validTypes = ['business', 'office', 'private', 'other'];
        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid email type: $type. Must be one of: ".implode(', ', $validTypes));
        }

        // Add or replace email address of this type
        $this->emailAddresses[$type] = [$email];

        return $this;
    }

    /**
     * Sets the phone numbers for the contact
     *
     * Note: The API supports only one phone number per type (business, office, mobile, private, fax, other)
     *
     * @param  array  $phoneNumbers  Array with phone numbers in API format
     * @return $this
     */
    public function setPhoneNumbers(array $phoneNumbers): self
    {
        $processedPhones = [];
        foreach ($phoneNumbers as $key => $value) {
            if (is_array($value)) {
                $processedPhones[$key] = $value;
            } else {
                $processedPhones[$key] = [$value];
            }
        }
        $this->phoneNumbers = $processedPhones;

        return $this;
    }

    /**
     * Adds a phone number to the contact
     *
     * Note: The API supports only one phone number per type (business, office, mobile, private, fax, other)
     *
     * @param  string  $number  The phone number
     * @param  string  $type  The type of phone number (business, office, mobile, private, fax, other)
     * @return $this
     */
    public function addPhoneNumber(string $number, string $type = 'business'): self
    {
        // Validate phone type
        $validTypes = ['business', 'office', 'mobile', 'private', 'fax', 'other'];
        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid phone type: $type. Must be one of: ".implode(', ', $validTypes));
        }

        // Add or replace phone number of this type
        $this->phoneNumbers[$type] = [$number];

        return $this;
    }

    private function setUpdatedDate(?string $updatedDate): self
    {
        $this->updatedDate = $updatedDate;

        return $this;
    }

    private function setCreatedDate(?string $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function setPerson(Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setOrganizationId(?string $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCustomerNumber(): ?int
    {
        return $this->roles['customer']['number'] ?? null;
    }

    public function getVendorNumber(): ?int
    {
        return $this->roles['vendor']['number'] ?? null;
    }

    public function isCustomer(): bool
    {
        return isset($this->roles['customer']);
    }

    public function isVendor(): bool
    {
        return isset($this->roles['vendor']);
    }

    public function getBillingAddress(): ?Address
    {
        return $this->addresses['billing'][0] ?? null;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->addresses['shipping'][0] ?? null;
    }
}
