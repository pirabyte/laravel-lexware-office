<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\Person;
use Pirabyte\LaravelLexwareOffice\Models\Address;
use Pirabyte\LaravelLexwareOffice\Models\XRechnung;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ContactMethodsTest extends TestCase
{
    public function test_it_can_set_a_contact_as_customer(): void
    {
        $contact = new Contact();
        $contact->setAsCustomer();
        $this->assertTrue($contact->isCustomer());
        $this->assertFalse($contact->isVendor());
        $this->assertArrayHasKey('customer', $contact->getRoles());
    }


    public function test_it_can_set_a_contact_as_vendor(): void
    {
        $contact = new Contact();
        $contact->setAsVendor();

        $this->assertTrue($contact->isVendor());
        $this->assertFalse($contact->isCustomer());
        $this->assertArrayHasKey('vendor', $contact->getRoles());
    }

    /** @test */
    public function it_can_add_a_billing_address(): void
    {
        $contact = new Contact();
        $contact->addBillingAddress('Main St 1', '12345', 'Anytown', 'US');

        $this->assertNotNull($contact->getBillingAddress());
        $this->assertInstanceOf(Address::class, $contact->getBillingAddress());
        $this->assertEquals('Main St 1', $contact->getBillingAddress()->getStreet());
        $this->assertEquals('12345', $contact->getBillingAddress()->getZip());
        $this->assertEquals('Anytown', $contact->getBillingAddress()->getCity());
        $this->assertEquals('US', $contact->getBillingAddress()->getCountryCode());
    }

    /** @test */
    public function it_can_add_a_shipping_address(): void
    {
        $contact = new Contact();
        $contact->addShippingAddress('Side St 2', '54321', 'Othertown', 'CA', 'Suite 100');

        $this->assertNotNull($contact->getShippingAddress());
        $this->assertInstanceOf(Address::class, $contact->getShippingAddress());
        $this->assertEquals('Side St 2', $contact->getShippingAddress()->getStreet());
        $this->assertEquals('54321', $contact->getShippingAddress()->getZip());
        $this->assertEquals('Othertown', $contact->getShippingAddress()->getCity());
        $this->assertEquals('CA', $contact->getShippingAddress()->getCountryCode());
        $this->assertEquals('Suite 100', $contact->getShippingAddress()->getSupplement());
    }

    /** @test */
    public function it_can_add_an_email_address(): void
    {
        $contact = new Contact();
        $contact->addEmailAddress('test@example.com', 'business');

        $this->assertEquals('test@example.com', $contact->getEmailAddress('business'));
        $this->assertNull($contact->getEmailAddress('private'));
    }

    /** @test */
    public function it_can_add_a_phone_number(): void
    {
        $contact = new Contact();
        $contact->addPhoneNumber('+123456789', 'mobile');

        $this->assertEquals('+123456789', $contact->getPhoneNumber('mobile'));
        $this->assertNull($contact->getPhoneNumber('business'));
    }

    /** @test */
    public function it_can_create_a_person_contact(): void
    {
        $contact = Contact::createPerson('John', 'Doe', 'Mr.');

        $this->assertNotNull($contact->getPerson());
        $this->assertInstanceOf(Person::class, $contact->getPerson());
        $this->assertEquals('John', $contact->getPerson()->getFirstName());
        $this->assertEquals('Doe', $contact->getPerson()->getLastName());
        $this->assertEquals('Mr.', $contact->getPerson()->getSalutation());
        $this->assertNull($contact->getCompany());
    }

    /** @test */
    public function it_can_create_a_company_contact(): void
    {
        $contact = Contact::createCompany('Acme Corp');

        $this->assertNotNull($contact->getCompany());
        $this->assertEquals('Acme Corp', $contact->getCompany()->getName());
        $this->assertNull($contact->getPerson());
    }

    /** @test */
    public function it_can_set_and_get_note(): void
    {
        $contact = new Contact();
        $contact->setNote('This is a test note.');

        $this->assertEquals('This is a test note.', $contact->getNote());
    }

    /** @test */
    public function it_can_set_and_get_archived_status(): void
    {
        $contact = new Contact();
        $this->assertFalse($contact->isArchived());

        $contact->setArchived(true);
        $this->assertTrue($contact->isArchived());
        $this->assertTrue($contact->getArchived());

        $contact->setArchived(false);
        $this->assertFalse($contact->isArchived());
        $this->assertFalse($contact->getArchived());
    }

    /** @test */
    public function it_can_set_and_get_id(): void
    {
        $contact = new Contact();
        $contact->setId('some-uuid-123');

        $this->assertEquals('some-uuid-123', $contact->getId());
    }

    /** @test */
    public function it_can_set_and_get_version(): void
    {
        $contact = new Contact();
        $contact->setVersion(5);

        $this->assertEquals(5, $contact->getVersion());
    }

    /** @test */
    public function it_can_set_and_get_xrechnung(): void
    {
        $contact = new Contact();
        $xRechnung = new XRechnung();
        $xRechnung->setBuyerReference('12345');
        $contact->setXRechnung($xRechnung);

        $this->assertNotNull($contact->getXRechnung());
        $this->assertEquals('12345', $contact->getXRechnung()->getBuyerReference());
    }

    /** @test */
    public function it_can_set_email_addresses_from_array(): void
    {
        $contact = new Contact();
        $contact->setEmailAddresses([
            'business' => ['business@example.com'],
            'private' => ['private@example.com'],
        ]);

        $this->assertEquals('business@example.com', $contact->getEmailAddress('business'));
        $this->assertEquals('private@example.com', $contact->getEmailAddress('private'));
    }

    /** @test */
    public function it_can_set_phone_numbers_from_array(): void
    {
        $contact = new Contact();
        $contact->setPhoneNumbers([
            'business' => ['+111111111'],
            'mobile' => ['+222222222'],
        ]);

        $this->assertEquals('+111111111', $contact->getPhoneNumber('business'));
        $this->assertEquals('+222222222', $contact->getPhoneNumber('mobile'));
    }

    /** @test */
    public function it_can_get_customer_number(): void
    {
        $contact = new Contact();
        $contact->setRoles(['customer' => ['number' => 123]]);

        $this->assertEquals(123, $contact->getCustomerNumber());
    }

    /** @test */
    public function it_can_get_vendor_number(): void
    {
        $contact = new Contact();
        $contact->setRoles(['vendor' => ['number' => 456]]);

        $this->assertEquals(456, $contact->getVendorNumber());
    }

    /** @test */
    public function it_handles_invalid_address_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid address type: invalid. Must be one of: billing, shipping');

        $contact = new Contact();
        $contact->addAddress(['street' => 'Test', 'zip' => '123', 'city' => 'Test', 'countryCode' => 'DE'], 'invalid');
    }

    /** @test */
    public function it_handles_invalid_email_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email type: invalid. Must be one of: business, office, private, other');

        $contact = new Contact();
        $contact->addEmailAddress('test@test.com', 'invalid');
    }

    /** @test */
    public function it_handles_invalid_phone_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone type: invalid. Must be one of: business, office, mobile, private, fax, other');

        $contact = new Contact();
        $contact->addPhoneNumber('123', 'invalid');
    }
}