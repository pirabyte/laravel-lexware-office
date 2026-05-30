<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Pirabyte\LaravelLexwareOffice\Dto\TransactionAssignmentHints\TransactionAssignmentHint;
use Pirabyte\LaravelLexwareOffice\Http\LexwareHttpClient;
use Pirabyte\LaravelLexwareOffice\Mappers\TransactionAssignmentHints\TransactionAssignmentHintMapper;
use Pirabyte\LaravelLexwareOffice\Mappers\TransactionAssignmentHints\TransactionAssignmentHintWriteMapper;

class TransactionAssignmentHintResource
{
    public function __construct(private readonly LexwareHttpClient $http) {}

    /**
     * Erstellt einen Transaction Assignment Hint
     *
     * Hinweis: Nur ein einziger Assignment Hint oder eine eindeutige ID ist pro Beleg erlaubt.
     * Wenn mehrere oder sowohl externalReference als auch endToEndId angegeben werden,
     * wird die Anfrage mit einem 406-Fehler abgelehnt.
     *
     * @param  TransactionAssignmentHint  $hint  Das zu erstellende TransactionAssignmentHint-Objekt
     * @return TransactionAssignmentHint Das erstellte TransactionAssignmentHint-Objekt
     */
    public function create(TransactionAssignmentHint $hint): TransactionAssignmentHint
    {
        $body = TransactionAssignmentHintWriteMapper::toJsonBody($hint);
        $response = $this->http->postJson('transaction-assignment-hint', $body);

        return TransactionAssignmentHintMapper::fromJson($response->body);
    }
}
