<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Facades;

use Illuminate\Support\Facades\Facade;
use Pirabyte\LaravelLexwareOffice\Resources\ContactResource;
use Pirabyte\LaravelLexwareOffice\Resources\CountryResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialAccountResource;
use Pirabyte\LaravelLexwareOffice\Resources\FinancialTransactionResource;
use Pirabyte\LaravelLexwareOffice\Resources\PartnerIntegrationResource;
use Pirabyte\LaravelLexwareOffice\Resources\PostingCategoryResource;
use Pirabyte\LaravelLexwareOffice\Resources\ProfileResource;
use Pirabyte\LaravelLexwareOffice\Resources\TransactionAssignmentHintResource;
use Pirabyte\LaravelLexwareOffice\Resources\VoucherResource;

/**
 * @method static ContactResource contacts()
 * @method static VoucherResource vouchers()
 * @method static ProfileResource profile()
 * @method static PostingCategoryResource postingCategories()
 * @method static CountryResource countries()
 * @method static FinancialAccountResource financialAccounts()
 * @method static FinancialTransactionResource financialTransactions()
 * @method static TransactionAssignmentHintResource transactionAssignmentHints()
 * @method static PartnerIntegrationResource partnerIntegrations()
 * @method setRateLimitKey(string $key)
 * @method setRateLimit(int $maxRequests)
 */
class LexwareOffice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'lexware-office';
    }
}
