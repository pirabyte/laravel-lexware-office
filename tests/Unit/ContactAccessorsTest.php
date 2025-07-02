<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\Models\Address;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class ContactAccessorsTest extends TestCase
{
    /** @test */
    public function it_can_get_customer_and_vendor_numbers(): void
    {
        $contact = new Contact();
        $contact->setRoles([
            'customer' => ['number' => 12345],
            'vendor' => ['number' => 54321],
        ]);

        $this->assertEquals(12345, $contact->getCustomerNumber());
        $this->assertEquals(54321, $contact->getVendorNumber());
    }

    /** @test */
    public function it_returns_null_for_non_existent_roles(): void
    {
        $contact = new Contact();

        $this->assertNull($contact->getCustomerNumber());
        $this->assertNull($contact->getVendorNumber());
    }

    /** @test */
    public function it_can_check_if_contact_is_customer_or_vendor(): void
    {
        $contact = new Contact();
        $contact->setRoles([
            'customer' => ['number' => 12345],
        ]);

        $this->assertTrue($contact->isCustomer());
        $this->assertFalse($contact->isVendor());
    }

    /** @test */
    public function it_can_get_billing_and_shipping_addresses(): void
    {
        $billingAddress = new Address();
        $billingAddress->street = 'Billing Street';
        $billingAddress->zip = '12345';
        $billingAddress->city = 'Billing City';
        $billingAddress->countryCode = 'DE';

        $shippingAddress = new Address();
        $shippingAddress->street = 'Shipping Street';
        $shippingAddress->zip = '54321';
        $shippingAddress->city = 'Shipping City';
        $shippingAddress->countryCode = 'DE';

        $contact = new Contact();
        $contact->setAddresses([
            'billing' => [$billingAddress],
            'shipping' => [$shippingAddress],
        ]);

        $this->assertEquals($billingAddress, $contact->getBillingAddress());
        $this->assertEquals($shippingAddress, $contact->getShippingAddress());
    }

    /** @test */
    public function it_returns_null_for_non_existent_addresses(): void
    {
        $contact = new Contact();

        $this->assertNull($contact->getBillingAddress());
        $this->assertNull($contact->getShippingAddress());
    }
}
