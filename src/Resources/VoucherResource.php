<?php

namespace Pirabyte\LaravelLexwareOffice\Resources;

use GuzzleHttp\Exception\GuzzleException;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\Models\Voucher;
use Psr\Http\Message\StreamInterface;

class VoucherResource
{
    protected LexwareOffice $client;

    public function __construct(LexwareOffice $client)
    {
        $this->client = $client;
    }

    /**
     * Erstellt einen neuen Beleg
     *
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function create(Voucher $voucher): Voucher
    {
        $data = $voucher->jsonSerialize();
        $response = $this->client->post('vouchers', $data);

        // Holen des kompletten Belegs wenn ID vorhanden
        if (isset($response['id'])) {
            try {
                return $this->get($response['id']);
            } catch (\Exception $e) {
                // Fallback zur Datenzusammenführung wenn Get fehlschlägt
            }
        }

        return Voucher::fromArray(array_merge($data, $response));
    }

    /**
     * Ruft einen Beleg anhand der ID ab
     *
     * @throws LexwareOfficeApiException
     */
    public function get(string $id): Voucher
    {
        $response = $this->client->get("vouchers/{$id}");

        return Voucher::fromArray($response);
    }

    /**
     * Aktualisiert einen bestehenden Beleg
     *
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function update(string $id, Voucher $voucher): Voucher
    {
        $data = $voucher->jsonSerialize();
        $response = $this->client->put("vouchers/{$id}", $data);

        // Holen des kompletten Belegs wenn erfolgreich
        if (isset($response['id'])) {
            try {
                return $this->get($response['id']);
            } catch (\Exception $e) {
                // Fallback zur Datenzusammenführung wenn Get fehlschlägt
            }
        }

        return Voucher::fromArray(array_merge($data, $response));
    }

    /**
     * Belege nach verschiedenen Kriterien filtern
     *
     * @param  string  $voucherNumber  Filterung nach Belegnummer
     * @return array Liste der gefilterten Belege als Voucher-Objekte und Paginierungsinformationen
     *
     * @throws LexwareOfficeApiException
     */
    public function filter(string $voucherNumber): array
    {
        $query = [];

        if (! empty($voucherNumber)) {
            $query = [
                'voucherNumber' => $voucherNumber,
            ];
        }

        // API-Anfrage senden
        $response = $this->client->get('vouchers', $query);

        return $this->processVouchersResponse($response);
    }

    /**
     * Alle Belege abrufen mit Paginierung
     *
     * @param  int  $page  Seitennummer (beginnend bei 0)
     * @param  int  $size  Anzahl der Ergebnisse pro Seite (max. 100)
     * @return array Liste aller Belege als Voucher-Objekte und Paginierungsinformationen
     *
     * @throws LexwareOfficeApiException
     */
    public function all(int $page = 0, int $size = 25): array
    {
        $response = $this->client->get('vouchers', [
            'page' => $page,
            'size' => min($size, 100), // Maximal 100 Einträge pro Seite
        ]);

        return $this->processVouchersResponse($response);
    }

    /**
     * Verarbeitet die Antwort der Belege-API und erstellt daraus ein strukturiertes Array
     *
     * @param  array  $response  API-Antwort
     * @return array Strukturiertes Array mit Belegen und Paginierungsinformationen
     */
    protected function processVouchersResponse(array $response): array
    {
        $result = [
            'content' => [],
            'pagination' => [
                'page' => $response['page'] ?? 0,
                'size' => $response['size'] ?? 0,
                'totalPages' => $response['totalPages'] ?? 0,
                'totalElements' => $response['totalElements'] ?? 0,
                'numberOfElements' => $response['numberOfElements'] ?? 0,
            ],
        ];

        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $voucherData) {
                $result['content'][] = Voucher::fromArray($voucherData);
            }
        }

        return $result;
    }

    /**
     * Generiert ein Dokument (z.B. PDF) für einen Beleg
     *
     * @param  string  $id  Beleg-ID
     * @return array Informationen zum generierten Dokument (fileId, fileName, mimeType, etc.)
     *
     * @throws LexwareOfficeApiException|GuzzleException
     */
    public function document(string $id): array
    {
        return $this->client->post("vouchers/{$id}/document", []);
    }

    /**
     * Lädt ein Dokument für einen Beleg herunter
     *
     * @param  string  $voucherId  Beleg-ID
     * @param  string  $fileId  Datei-ID
     * @return array Dateiinhalt und Metadaten
     *
     * @throws LexwareOfficeApiException
     */
    public function downloadDocument(string $voucherId, string $fileId): array
    {
        return $this->client->get("vouchers/{$voucherId}/files/{$fileId}");
    }

    /**
     * Fügt eine Datei an einen Beleg an
     *
     * @param  string  $id  Beleg-ID
     * @param  StreamInterface  $stream  Datei-Inhalt als Stream
     * @param  string  $filename  Optional: Name der Datei (Standard: voucher.pdf)
     * @param  string  $type  Optional: Typ der Datei (Standard: voucher)
     * @return array Informationen zur angehängten Datei
     *
     * @throws LexwareOfficeApiException
     * @throws GuzzleException
     */
    public function attachFile(string $id, StreamInterface $stream, string $filename = 'voucher.pdf', string $type = 'voucher'): array
    {
        $endpoint = "vouchers/{$id}/files";

        $options = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $stream,
                    'filename' => $filename,
                ],
                [
                    'name' => 'type',
                    'contents' => $type,
                ],
            ],
        ];
        $response = $this->client->client()->request('POST', $endpoint, $options);

        return json_decode($response->getBody()->getContents(), true);
    }
}
