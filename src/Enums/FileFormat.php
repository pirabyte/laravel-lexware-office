<?php

namespace Pirabyte\LaravelLexwareOffice\Enums;

enum FileFormat: string
{
    case DEFAULT = '*/*';
    case PDF = 'application/pdf';
    case XML = 'application/xml';
}
