<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

class Invoice extends SalesVoucher
{
    public readonly ?string $dueDate;

    public readonly ?XRechnung $xRechnung;

    public readonly ?PaymentConditions $paymentConditions;

    public readonly ShippingConditions $shippingConditions;

    public readonly bool $closingInvoice;

    public readonly ?float $claimedGrossAmount;

    public readonly ?string $recurringTemplateId;

    /** @var list<array<string, mixed>>|null */
    public readonly ?array $downPaymentDeductions;

    protected function __construct(array $data)
    {
        parent::__construct($data);
        $this->dueDate = $data['dueDate'] ?? null;
        $this->xRechnung = isset($data['xRechnung']) ? XRechnung::fromArray($data['xRechnung']) : null;
        $this->paymentConditions = isset($data['paymentConditions']) ? PaymentConditions::fromArray($data['paymentConditions']) : null;
        $this->shippingConditions = ShippingConditions::fromArray($data['shippingConditions']);
        $this->closingInvoice = $data['closingInvoice'] ?? false;
        $this->claimedGrossAmount = isset($data['claimedGrossAmount']) ? (float) $data['claimedGrossAmount'] : null;
        $this->recurringTemplateId = $data['recurringTemplateId'] ?? null;
        $this->downPaymentDeductions = $data['downPaymentDeductions'] ?? null;
    }
}
