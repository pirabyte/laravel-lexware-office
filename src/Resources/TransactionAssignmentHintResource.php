<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\TransactionAssignmentHint;

class TransactionAssignmentHintResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Erstellt einen Transaction Assignment Hint
     * 
     * Hinweis: Nur ein einziger Assignment Hint oder eine eindeutige ID ist pro Beleg erlaubt.
     * Wenn mehrere oder sowohl externalReference als auch endToEndId angegeben werden,
     * wird die Anfrage mit einem 406-Fehler abgelehnt.
     * 
     * @param TransactionAssignmentHint $hint Das zu erstellende TransactionAssignmentHint-Objekt
     * @return TransactionAssignmentHint Das erstellte TransactionAssignmentHint-Objekt
     */
    public function create(TransactionAssignmentHint $hint): TransactionAssignmentHint
    {
        $response = $this->client->post('transaction-assignment-hint', $hint->jsonSerialize());
        return TransactionAssignmentHint::fromArray($response);
    }
}