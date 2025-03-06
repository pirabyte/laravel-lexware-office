<?php

namespace Pirabyte\LaravelLexwareOffice\Models;

use Pirabyte\LaravelLexwareOffice\Enums\TaxClassification;

class Country implements \JsonSerializable
{
    private string $countryCode;
    private string $countryNameEN;
    private string $countryNameDE;
    private TaxClassification $taxClassification;

    /**
     * Konstruktor mit erforderlichen Feldern
     * 
     * @param string $countryCode
     * @param string $countryNameEN
     * @param string $countryNameDE
     * @param TaxClassification $taxClassification
     */
    public function __construct(
        string $countryCode,
        string $countryNameEN,
        string $countryNameDE,
        TaxClassification $taxClassification
    ) {
        $this->countryCode = $countryCode;
        $this->countryNameEN = $countryNameEN;
        $this->countryNameDE = $countryNameDE;
        $this->taxClassification = $taxClassification;
    }

    /**
     * Konvertiert ein Array in eine Country-Instanz
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        // Enum von String konvertieren falls nötig
        $taxClassification = isset($data['taxClassification']) 
            ? (is_string($data['taxClassification']) 
                ? TaxClassification::from($data['taxClassification']) 
                : $data['taxClassification'])
            : TaxClassification::GERMANY; // Standardwert falls nicht gesetzt

        return new self(
            $data['countryCode'] ?? '',
            $data['countryNameEN'] ?? '',
            $data['countryNameDE'] ?? '',
            $taxClassification
        );
    }

    /**
     * Konvertiert die Country-Instanz in ein Array für JSON-Serialisierung
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'countryNameEN' => $this->countryNameEN,
            'countryNameDE' => $this->countryNameDE,
            'taxClassification' => $this->taxClassification->value,
        ];
    }

    /**
     * Gibt den Ländercode zurück
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Gibt den englischen Ländernamen zurück
     *
     * @return string
     */
    public function getCountryNameEN(): string
    {
        return $this->countryNameEN;
    }

    /**
     * Gibt den deutschen Ländernamen zurück
     *
     * @return string
     */
    public function getCountryNameDE(): string
    {
        return $this->countryNameDE;
    }

    /**
     * Gibt die Steuerklassifizierung zurück
     *
     * @return TaxClassification
     */
    public function getTaxClassification(): TaxClassification
    {
        return $this->taxClassification;
    }
}