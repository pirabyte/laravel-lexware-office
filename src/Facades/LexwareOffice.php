<?php

namespace Pirabyte\LaravelLexwareOffice\Facades;

use Illuminate\Support\Facades\Facade;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\CountryResource;
use Pirabyte\LaravelLexwareOffice\Resources\CreditNoteResource;
use Pirabyte\LaravelLexwareOffice\Resources\EventSubscriptionResource;
use Pirabyte\LaravelLexwareOffice\Resources\FileResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialAccountResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialTransactionResource;
use Pirabyte\LaravelLexwareOffice\Resources\InvoiceResource;
use Pirabyte\LaravelLexwareOffice\Resources\PaymentResource;
use Pirabyte\LaravelLexwareOffice\Resources\PostingCategoryResource;
use Pirabyte\LaravelLexwareOffice\Resources\PrintLayoutResource;
use Pirabyte\LaravelLexwareOffice\Resources\ProfileResource;
use Pirabyte\LaravelLexwareOffice\Resources\TransactionAssignmentHintResource;
use Pirabyte\LaravelLexwareOffice\Resources\VoucherResource;

/**
 * @method static InvoiceResource invoices()
 * @method static CreditNoteResource creditNotes()
 * @method static PrintLayoutResource printLayouts()
 * @method static PaymentResource payments()
 * @method static EventSubscriptionResource eventSubscriptions()
 * @method static FileResource files()
 * @method static ContactResource contacts()
 * @method static VoucherResource vouchers()
 * @method static ProfileResource profile()
 * @method static PostingCategoryResource postingCategories()
 * @method static CountryResource countries()
 * @method static FinancialAccountResource financialAccounts()
 * @method static FinancialTransactionResource financialTransactions()
 * @method static TransactionAssignmentHintResource transactionAssignmentHints()
 * @method setRateLimitKey(string $key)
 */
class LexwareOffice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'lexware-office';
    }
}
