<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use GuzzleHttp\Exception\GuzzleException;
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
}