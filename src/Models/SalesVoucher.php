<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\ElectronicDocumentProfile;
use Pirabyte\LaravelLexwareOffice\Enums\VoucherStatus;

/** @phpstan-consistent-constructor */
abstract class SalesVoucher
{
    public readonly string $id;

    public readonly string $organizationId;

    public readonly string $createdDate;

    public readonly string $updatedDate;

    public readonly int $version;

    public readonly string $language;

    public readonly bool $archived;

    public readonly VoucherStatus $voucherStatus;

    public readonly string $voucherNumber;

    public readonly string $voucherDate;

    public readonly SalesVoucherAddress $address;

    public readonly ElectronicDocumentProfile $electronicDocumentProfile;

    /** @var list<LineItem> */
    public readonly array $lineItems;

    public readonly TotalPrice $totalPrice;

    public readonly TaxConditions $taxConditions;

    /** @var list<TaxAmount> */
    public readonly array $taxAmounts;

    /** @var list<RelatedVoucher> */
    public readonly array $relatedVouchers;

    public readonly ?string $printLayoutId;

    public readonly ?string $title;

    public readonly ?string $introduction;

    public readonly ?string $remark;

    protected function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->organizationId = $data['organizationId'];
        $this->createdDate = $data['createdDate'];
        $this->updatedDate = $data['updatedDate'];
        $this->version = $data['version'];
        $this->language = $data['language'];
        $this->archived = $data['archived'];
        $this->voucherStatus = VoucherStatus::from($data['voucherStatus']);
        $this->voucherNumber = $data['voucherNumber'];
        $this->voucherDate = $data['voucherDate'];
        $this->address = SalesVoucherAddress::fromArray($data['address']);
        $this->electronicDocumentProfile = ElectronicDocumentProfile::from($data['electronicDocumentProfile']);
        $this->lineItems = array_map(LineItem::fromArray(...), $data['lineItems'] ?? []);
        $this->totalPrice = TotalPrice::fromArray($data['totalPrice']);
        $this->taxConditions = TaxConditions::fromArray($data['taxConditions']);
        $this->taxAmounts = array_map(TaxAmount::fromArray(...), $data['taxAmounts'] ?? []);
        $this->relatedVouchers = array_map(RelatedVoucher::fromArray(...), $data['relatedVouchers'] ?? []);
        $this->printLayoutId = $data['printLayoutId'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->introduction = $data['introduction'] ?? null;
        $this->remark = $data['remark'] ?? null;
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
