<?php

namespace Pirabyte\LaravelLexwareOffice\Requests;

use Pirabyte\LaravelLexwareOffice\Models\LineItem;
use Pirabyte\LaravelLexwareOffice\Models\PaymentConditions;
use Pirabyte\LaravelLexwareOffice\Models\SalesVoucherAddress;
use Pirabyte\LaravelLexwareOffice\Models\ShippingConditions;
use Pirabyte\LaravelLexwareOffice\Models\TaxConditions;
use Pirabyte\LaravelLexwareOffice\Models\TotalPrice;
use Pirabyte\LaravelLexwareOffice\Models\XRechnung;

class InvoiceCreateRequest extends SalesVoucherCreateRequest
{
    /** @param list<LineItem> $lineItems */
    public function __construct(
        string $voucherDate,
        SalesVoucherAddress $address,
        array $lineItems,
        TotalPrice $totalPrice,
        TaxConditions $taxConditions,
        public readonly ShippingConditions $shippingConditions,
        public readonly ?PaymentConditions $paymentConditions = null,
        public readonly ?XRechnung $xRechnung = null,
        ?string $language = null,
        ?string $printLayoutId = null,
        ?string $title = null,
        ?string $introduction = null,
        ?string $remark = null,
    ) {
        parent::__construct(
            $voucherDate, $address, $lineItems, $totalPrice, $taxConditions,
            $language, $printLayoutId, $title, $introduction, $remark,
        );
    }
}
