<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Pirabyte\LaravelLexwareOffice\Enums\ElectronicDocumentProfile;
use Pirabyte\LaravelLexwareOffice\Enums\LineItemType;
use Pirabyte\LaravelLexwareOffice\Enums\ShippingType;
use Pirabyte\LaravelLexwareOffice\Enums\TaxType;
use Pirabyte\LaravelLexwareOffice\Enums\VoucherStatus;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;
use Pirabyte\LaravelLexwareOffice\Models\LineItem;
use Pirabyte\LaravelLexwareOffice\Models\PaymentConditions;
use Pirabyte\LaravelLexwareOffice\Models\SalesVoucherAddress;
use Pirabyte\LaravelLexwareOffice\Models\ShippingConditions;
use Pirabyte\LaravelLexwareOffice\Models\TaxConditions;
use Pirabyte\LaravelLexwareOffice\Models\TotalPrice;
use Pirabyte\LaravelLexwareOffice\Models\UnitPrice;
use Pirabyte\LaravelLexwareOffice\Models\XRechnung;
use Pirabyte\LaravelLexwareOffice\Requests\CreditNoteCreateRequest;
use Pirabyte\LaravelLexwareOffice\Requests\EventSubscriptionCreateRequest;
use Pirabyte\LaravelLexwareOffice\Requests\InvoiceCreateRequest;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class InvoicingResourceTest extends TestCase
{
    private array $history = [];

    public function test_create_invoice_serializes_writable_fields_and_finalization_in_one_request(): void
    {
        $client = $this->clientWith([$this->jsonResponse($this->actionResult(), 201)]);
        $fixture = $this->fixture('invoice');
        $contact = Contact::fromArray(['id' => 'customer-id']);
        $request = new InvoiceCreateRequest(
            voucherDate: $fixture['voucherDate'],
            address: SalesVoucherAddress::fromContact($contact),
            lineItems: array_map(LineItem::fromArray(...), $fixture['lineItems']),
            totalPrice: TotalPrice::fromArray($fixture['totalPrice']),
            taxConditions: TaxConditions::fromArray($fixture['taxConditions']),
            shippingConditions: ShippingConditions::fromArray($fixture['shippingConditions']),
            paymentConditions: PaymentConditions::fromArray($fixture['paymentConditions']),
            xRechnung: XRechnung::fromArray(['buyerReference' => 'buyer', 'vendorNumberAtCustomer' => 'vendor']),
            printLayoutId: 'layout-id',
            remark: '',
        );

        $result = $client->invoices()->create($request, finalize: true);

        $this->assertSame('created-id', $result->getId());
        $this->assertSame(0, $result->getVersion());
        $this->assertCount(1, $this->history);
        $http = $this->history[0]['request'];
        $this->assertSame('POST', $http->getMethod());
        $this->assertSame('/v1/invoices', $http->getUri()->getPath());
        $this->assertSame('finalize=true', $http->getUri()->getQuery());
        $body = json_decode((string) $http->getBody(), true);
        $this->assertSame(['contactId' => 'customer-id'], $body['address']);
        $this->assertSame(['buyerReference' => 'buyer', 'vendorNumberAtCustomer' => 'vendor'], $body['xRechnung']);
        $this->assertSame('net', $body['taxConditions']['taxType']);
        $this->assertSame('delivery', $body['shippingConditions']['shippingType']);
        $this->assertSame('layout-id', $body['printLayoutId']);
        $this->assertSame('', $body['remark']);
        $this->assertArrayNotHasKey('electronicDocumentProfile', $body);
        $this->assertArrayNotHasKey('voucherNumber', $body);
        $this->assertArrayNotHasKey('taxAmounts', $body);
        $this->assertArrayNotHasKey('lineItemAmount', $body['lineItems'][0]);
        $this->assertArrayNotHasKey('totalGrossAmount', $body['totalPrice']);
        $this->assertArrayNotHasKey('paymentTermLabelTemplate', $body['paymentConditions']);
        $this->assertSame(0, $body['lineItems'][2]['unitPrice']['taxRatePercentage']);
        $this->assertSame('text', $body['lineItems'][3]['type']);
        $this->assertArrayNotHasKey('unitPrice', $body['lineItems'][3]);
    }

    public function test_create_defaults_to_draft_and_supports_linked_and_standalone_credit_notes(): void
    {
        $client = $this->clientWith(array_map(fn () => $this->jsonResponse($this->actionResult(), 201), range(1, 3)));
        $lineItems = [new LineItem(LineItemType::CUSTOM, 'Leistung', quantity: 1, unitName: 'Stück', unitPrice: new UnitPrice(netAmount: 10))];
        $address = new SalesVoucherAddress(name: 'Kunde', countryCode: 'DE');
        $total = new TotalPrice(totalDiscountAbsolute: 0);
        $tax = new TaxConditions(TaxType::NET);
        $credit = new CreditNoteCreateRequest('2026-11-01T00:00:00+01:00', $address, $lineItems, $total, $tax);
        $invoice = new InvoiceCreateRequest($credit->voucherDate, $address, $lineItems, $total, $tax, new ShippingConditions(ShippingType::NONE));

        $client->invoices()->create($invoice);
        $client->creditNotes()->create($credit, finalize: true, precedingSalesVoucherId: 'invoice-id');
        $client->creditNotes()->create($credit);

        $this->assertSame('finalize=false', $this->history[0]['request']->getUri()->getQuery());
        $linked = $this->history[1]['request'];
        $this->assertSame('/v1/credit-notes', $linked->getUri()->getPath());
        $this->assertSame('finalize=true&precedingSalesVoucherId=invoice-id', $linked->getUri()->getQuery());
        $this->assertSame('finalize=false', $this->history[2]['request']->getUri()->getQuery());
        $body = json_decode((string) $linked->getBody(), true);
        $this->assertSame(['currency' => 'EUR', 'totalDiscountAbsolute' => 0], $body['totalPrice']);
        $this->assertArrayNotHasKey('shippingConditions', $body);
        $this->assertArrayNotHasKey('xRechnung', $body);
    }

    public function test_get_deserializes_invoice_and_credit_note_details(): void
    {
        $invoiceData = $this->fixture('invoice');
        $invoiceData['electronicDocumentProfile'] = 'XRechnung';
        $invoiceData['xRechnung'] = ['buyerReference' => 'buyer', 'vendorNumberAtCustomer' => 'vendor'];
        $creditData = $this->fixture('credit-note');
        $creditData['voucherStatus'] = 'paidoff';
        $creditData['electronicDocumentProfile'] = 'EN16931';
        $creditData['relatedVouchers'] = [['id' => 'invoice-id', 'voucherNumber' => 'RE1019', 'voucherType' => 'invoice']];
        $client = $this->clientWith([$this->jsonResponse($invoiceData), $this->jsonResponse($creditData)]);

        $invoice = $client->invoices()->get('invoice-id');
        $credit = $client->creditNotes()->get('credit-id');

        $this->assertSame('RE1019', $invoice->voucherNumber);
        $this->assertSame(ElectronicDocumentProfile::XRECHNUNG, $invoice->electronicDocumentProfile);
        $this->assertSame('buyer', $invoice->xRechnung->getBuyerReference());
        $this->assertSame(13.4, $invoice->lineItems[0]->lineItemAmount);
        $this->assertSame(29.85, $invoice->totalPrice->totalGrossAmount);
        $this->assertSame(0.0, $invoice->taxAmounts[0]->taxAmount);
        $this->assertSame(ShippingType::DELIVERY, $invoice->shippingConditions->shippingType);
        $this->assertSame(3.0, $invoice->paymentConditions->paymentDiscountConditions->discountPercentage);
        $this->assertFalse($invoice->closingInvoice);
        $this->assertNull($invoice->dueDate);
        $this->assertSame(VoucherStatus::PAIDOFF, $credit->voucherStatus);
        $this->assertSame(ElectronicDocumentProfile::EN16931, $credit->electronicDocumentProfile);
        $this->assertSame('invoice-id', $credit->relatedVouchers[0]->id);
        $this->assertSame('/v1/invoices/invoice-id', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame('/v1/credit-notes/credit-id', $this->history[1]['request']->getUri()->getPath());
    }

    public function test_get_supports_draft_none_profile_and_description_only_text_items(): void
    {
        $data = $this->fixture('invoice');
        $data['lineItems'] = [['type' => 'text', 'description' => 'Hinweis']];
        $client = $this->clientWith([$this->jsonResponse($data)]);

        $invoice = $client->invoices()->get('draft-id');

        $this->assertSame(ElectronicDocumentProfile::NONE, $invoice->electronicDocumentProfile);
        $this->assertSame(VoucherStatus::DRAFT, $invoice->voucherStatus);
        $this->assertNull($invoice->lineItems[0]->name);
        $this->assertNull($invoice->lineItems[0]->unitPrice);
    }

    public function test_lists_layouts_as_a_bare_array_and_subscriptions_from_content(): void
    {
        $subscription = $this->subscription();
        $client = $this->clientWith([
            $this->jsonResponse([['id' => 'layout-id', 'name' => 'Standard', 'default' => true]]),
            $this->jsonResponse(['content' => [$subscription]]),
            $this->jsonResponse([]), $this->jsonResponse(['content' => []]),
        ]);

        $this->assertTrue($client->printLayouts()->all()[0]->default);
        $this->assertSame('invoice.status.changed', $client->eventSubscriptions()->all()[0]->eventType);
        $this->assertSame([], $client->printLayouts()->all());
        $this->assertSame([], $client->eventSubscriptions()->all());
        $this->assertSame('/v1/print-layouts', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame('/v1/event-subscriptions', $this->history[1]['request']->getUri()->getPath());
    }

    public function test_creates_gets_and_deletes_event_subscriptions(): void
    {
        $client = $this->clientWith([$this->jsonResponse($this->actionResult(), 201), $this->jsonResponse($this->subscription()), new Response(204)]);
        $request = new EventSubscriptionCreateRequest('invoice.status.changed', 'https://example.org/webhook');

        $result = $client->eventSubscriptions()->create($request);
        $subscription = $client->eventSubscriptions()->get('subscription-id');
        $client->eventSubscriptions()->delete('subscription-id');

        $this->assertSame('created-id', $result->getId());
        $this->assertSame('subscription-id', $subscription->subscriptionId);
        $this->assertSame('https://example.org/webhook', $subscription->callbackUrl);
        $this->assertSame($request->jsonSerialize(), json_decode((string) $this->history[0]['request']->getBody(), true));
        $this->assertSame('POST', $this->history[0]['request']->getMethod());
        $this->assertSame('GET', $this->history[1]['request']->getMethod());
        $this->assertSame('DELETE', $this->history[2]['request']->getMethod());
        $this->assertSame('/v1/event-subscriptions/subscription-id', $this->history[2]['request']->getUri()->getPath());
    }

    public function test_get_payment_preserves_open_amount_partial_items_and_paid_date(): void
    {
        $payment = ['openAmount' => '39.90', 'currency' => 'EUR', 'paymentStatus' => 'openRevenue', 'voucherType' => 'invoice', 'voucherStatus' => 'open',
            'paymentItems' => [['paymentItemType' => 'manualPayment', 'postingDate' => '2026-11-01T00:00:00+01:00', 'amount' => 10.5, 'currency' => 'EUR']]];
        $paid = array_replace($payment, ['openAmount' => 0, 'voucherStatus' => 'paid', 'paymentStatus' => 'balanced', 'paidDate' => '2026-11-01T00:00:00+01:00']);
        $client = $this->clientWith([$this->jsonResponse($payment), $this->jsonResponse($paid)]);

        $open = $client->payments()->get('voucher-id');
        $balanced = $client->payments()->get('voucher-id');

        $this->assertSame(39.9, $open->openAmount);
        $this->assertNull($open->paidDate);
        $this->assertSame(10.5, $open->paymentItems[0]->amount);
        $this->assertSame(0.0, $balanced->openAmount);
        $this->assertSame(VoucherStatus::PAID, $balanced->voucherStatus);
        $this->assertSame('2026-11-01T00:00:00+01:00', $balanced->paidDate);
        $this->assertSame('/v1/payments/voucher-id', $this->history[0]['request']->getUri()->getPath());
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

    private function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(__DIR__.'/../Fixtures/invoicing/'.$name.'.json'), true);
    }

    private function actionResult(): array
    {
        return ['id' => 'created-id', 'resourceUri' => 'https://api.lexware.io/v1/invoices/created-id', 'createdDate' => '2026-11-01T00:00:00+01:00', 'updatedDate' => '2026-11-01T00:00:00+01:00', 'version' => 0];
    }

    private function subscription(): array
    {
        return ['subscriptionId' => 'subscription-id', 'organizationId' => 'org-id', 'createdDate' => '2026-11-01T00:00:00+01:00', 'eventType' => 'invoice.status.changed', 'callbackUrl' => 'https://example.org/webhook'];
    }
}
