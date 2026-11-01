<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum LineItemType: string
{
    case CUSTOM = 'custom';
    case MATERIAL = 'material';
    case SERVICE = 'service';
    case TEXT = 'text';
}
