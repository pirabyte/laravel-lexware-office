<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum VoucherStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PAID = 'paid';
    case PAIDOFF = 'paidoff';
    case VOIDED = 'voided';
    case TRANSFERRED = 'transferred';
    case SEPADEBIT = 'sepadebit';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
