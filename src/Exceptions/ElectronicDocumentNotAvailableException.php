<?php

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Pirabyte\LaravelLexwareOffice\Enums\ElectronicDocumentProfile;

final class ElectronicDocumentNotAvailableException extends LexwareOfficeApiException
{
    public function __construct(
        public readonly ElectronicDocumentProfile $profile,
        ?LexwareOfficeApiException $previous = null,
    ) {
        $message = match ($profile) {
            ElectronicDocumentProfile::EN16931 => 'ZUGFeRD documents (EN16931) do not have a separate XML file. The XML is embedded in the PDF; download the file in the default or PDF format.',
            ElectronicDocumentProfile::NONE => 'This document has no electronic format (profile NONE), only a PDF. Check that e-invoicing is enabled in Lexware, the recipient is a company contact, and the company details including bank details are complete.',
            ElectronicDocumentProfile::XRECHNUNG => 'The XRechnung XML document is not available.',
        };

        parent::__construct($message, self::STATUS_NOT_FOUND, $previous?->getRequestException());
    }
}
