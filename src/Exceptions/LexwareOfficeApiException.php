<?php

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Exception;

class LexwareOfficeApiException extends Exception
{
    protected $statusCode;

    protected $responseData;

    public function __construct($message, $statusCode = 500, $previous = null)
    {
        $this->statusCode = $statusCode;

        // Versuche den JSON-Response zu parsen
        $responseData = json_decode($message, true);
        $this->responseData = $responseData ?: ['message' => $message];

        $errorMessage = is_array($responseData) && isset($responseData['message'])
            ? $responseData['message']
            : $message;

        parent::__construct($errorMessage, $statusCode, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }
}
