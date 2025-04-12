<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use Generator;
use GuzzleHttp\Exception\GuzzleException;
use Pirabyte\LaravelLexwareOffice\Classes\PaginatedResource;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Contact;

class ContactResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Erstellt einen neuen Kontakt
     *
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function create(Contact $contact): Contact
    {
        $data = $contact->jsonSerialize();
        $response = $this->client->post('contacts', $data);

        // Holen des kompletten Kontakts wenn ID vorhanden
        if (isset($response['id'])) {
            try {
                return $this->get($response['id']);
            } catch (\Exception $e) {
                // Fallback zur Datenzusammenführung wenn Get fehlschlägt
            }
        }

        return Contact::fromArray(array_merge($data, $response));
    }

    /**
     * Ruft einen Kontakt anhand der ID ab
     *
     * @throws LexwareOfficeApiException
     */
    public function get(string $id): Contact
    {
        $response = $this->client->get("contacts/{$id}");

        return Contact::fromArray($response);
    }

    /**
     * Aktualisiert einen bestehenden Kontakt
     *
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function update(string $id, Contact $contact): Contact
    {
        $data = $contact->jsonSerialize();
        $response = $this->client->put("contacts/{$id}", $data);

        // Holen des kompletten Kontakts wenn erfolgreich
        if (isset($response['id'])) {
            try {
                return $this->get($response['id']);
            } catch (\Exception $e) {
                // Fallback zur Datenzusammenführung wenn Get fehlschlägt
            }
        }

        return Contact::fromArray(array_merge($data, $response));
    }

    /**
     * Kontakte nach verschiedenen Kriterien filtern
     *
     * @param  array  $filters  Filtermöglichkeiten:
     *                          - customer: bool - Filtert nach Kunden
     *                          - vendor: bool - Filtert nach Lieferanten
     *                          - name: string - Filtert nach Namen (Firmen oder Personen)
     *                          - email: string - Filtert nach E-Mail-Adresse
     *                          - number: string - Filtert nach Kunden-/Lieferantennummer
     *                          - page: int - Seitennummer (beginnend bei 0)
     *                          - size: int - Anzahl der Ergebnisse pro Seite (max. 100)
     * @return PaginatedResource Liste der gefilterten Kontakte als PaginatedResource
     *
     * @throws LexwareOfficeApiException
     */
    public function filter(array $filters = []): PaginatedResource
    {
        $validFilters = [
            'customer', 'vendor', 'name', 'email', 'number', 'page', 'size',
        ];

        // HTTP-Query vorbereiten
        $query = [];

        // Wir müssen doppelte Filter korrekt handhaben (API kombiniert diese mit AND)
        foreach ($filters as $key => $value) {
            // Leere oder ungültige Filter überspringen
            if (! in_array($key, $validFilters) || $value === null || $value === '') {
                continue;
            }

            // Wenn der Filter bereits existiert, konvertieren wir ihn zu einem Array
            if (isset($query[$key])) {
                // Wenn es bereits ein Array ist, fügen wir den neuen Wert hinzu
                if (is_array($query[$key])) {
                    $query[$key][] = $value;
                } else {
                    // Andernfalls konvertieren wir es zu einem Array mit beiden Werten
                    $query[$key] = [$query[$key], $value];
                }
            } else {
                // Bei einem neuen Filter-Key setzen wir den Wert einfach
                $query[$key] = $value;
            }
        }

        // API-Anfrage senden
        $response = $this->client->get('contacts', $query);

        return $this->processContactsResponse($response);
    }

    /**
     * Alle Kontakte abrufen mit Paginierung
     *
     * @param  int  $page  Seitennummer (beginnend bei 0)
     * @param  int  $size  Anzahl der Ergebnisse pro Seite (max. 250)
     * @return PaginatedResource Liste aller Kontakte als PaginatedResource und Paginierungsinformationen
     *
     * @throws LexwareOfficeApiException
     */
    public function all(int $page = 0, int $size = 25): PaginatedResource
    {
        $response = $this->client->get('contacts', [
            'page' => $page,
            'size' => min($size, 250), // Maximal 250 Einträge pro Seite
        ]);

        return $this->processContactsResponse($response);
    }

    /**
     * Returns the total number of contacts
     *
     * @throws LexwareOfficeApiException
     */
    public function count(): int
    {
        $paginator = $this->all(1, 1);

        return $paginator->getTotal();
    }

    /**
     * Verarbeitet die Antwort der Kontakt-API und erstellt daraus ein strukturiertes Array
     *
     * @param  array  $response  API-Antwort
     * @return PaginatedResource Strukturiertes Array mit Kontakten und Paginierungsinformationen
     */
    protected function processContactsResponse(array $response): PaginatedResource
    {
        $resource = PaginatedResource::fromArray($response);

        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $contactData) {
                $resource->appendContent(Contact::fromArray($contactData));
            }
        }

        return $resource;
    }

    /**
     * Liefert einen Generator, der alle Kontakte automatisch paginiert durchläuft
     *
     * @param  array  $filters  Filtermöglichkeiten wie in der filter()-Methode:
     *                          - customer: bool - Filtert nach Kunden
     *                          - vendor: bool - Filtert nach Lieferanten
     *                          - name: string - Filtert nach Namen (Firmen oder Personen)
     *                          - email: string - Filtert nach E-Mail-Adresse
     *                          - number: string - Filtert nach Kunden-/Lieferantennummer
     * @param  int  $size  Anzahl der Ergebnisse pro Seite (max. 250)
     * @return Generator<Contact>
     *
     * @throws LexwareOfficeApiException
     */
    public function getAutoPagingIterator(array $filters = [], int $size = 25): Generator
    {
        $page = 0;
        $hasMore = true;

        // Filter-Array mit size Parameter vorbereiten
        $queryFilters = $filters;
        $queryFilters['size'] = min($size, 250);

        while ($hasMore) {
            $queryFilters['page'] = $page;

            // Aktuelle Seite laden
            $paginatedResource = $this->filter($queryFilters);

            // Keine Ergebnisse mehr, Schleife beenden
            if (empty($paginatedResource->jsonSerialize()['content'])) {
                break;
            }

            // Alle Kontakte der aktuellen Seite zurückgeben
            foreach ($paginatedResource->jsonSerialize()['content'] as $contact) {
                yield $contact;
            }

            // Prüfen ob es weitere Seiten gibt
            $hasMore = ! $paginatedResource->jsonSerialize()['last'];
            $page++;
        }
    }
}
