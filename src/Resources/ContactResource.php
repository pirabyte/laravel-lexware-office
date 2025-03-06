<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

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
     * @param Contact $contact
     * @return Contact
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
     * @param string $id
     * @return Contact
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
     * @param string $id
     * @param Contact $contact
     * @return Contact
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
     * @param array $filters Filtermöglichkeiten:
     *                      - customer: bool - Filtert nach Kunden
     *                      - vendor: bool - Filtert nach Lieferanten
     *                      - name: string - Filtert nach Namen (Firmen oder Personen)
     *                      - email: string - Filtert nach E-Mail-Adresse
     *                      - number: string - Filtert nach Kunden-/Lieferantennummer
     *                      - page: int - Seitennummer (beginnend bei 0)
     *                      - size: int - Anzahl der Ergebnisse pro Seite (max. 100)
     * @return PaginatedResource Liste der gefilterten Kontakte als PaginatedResource
     * @throws LexwareOfficeApiException
     */
    public function filter(array $filters = []): PaginatedResource
    {
        $validFilters = [
            'customer', 'vendor', 'name', 'email', 'number', 'page', 'size'
        ];

        // Nur gültige Filter-Parameter verwenden
        $query = array_filter($filters, function ($key) use ($validFilters) {
            return in_array($key, $validFilters);
        }, ARRAY_FILTER_USE_KEY);

        // API-Anfrage senden
        $response = $this->client->get('contacts', $query);

        return $this->processContactsResponse($response);
    }

    /**
     * Alle Kontakte abrufen mit Paginierung
     *
     * @param int $page Seitennummer (beginnend bei 0)
     * @param int $size Anzahl der Ergebnisse pro Seite (max. 100)
     * @return PaginatedResource Liste aller Kontakte als PaginatedResource und Paginierungsinformationen
     * @throws LexwareOfficeApiException
     */
    public function all(int $page = 0, int $size = 25): PaginatedResource
    {
        $response = $this->client->get('contacts', [
            'page' => $page,
            'size' => min($size, 100) // Maximal 100 Einträge pro Seite
        ]);

        return $this->processContactsResponse($response);
    }

    /**
     * Verarbeitet die Antwort der Kontakt-API und erstellt daraus ein strukturiertes Array
     *
     * @param array $response API-Antwort
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
}