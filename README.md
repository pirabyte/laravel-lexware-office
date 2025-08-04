![Pirabyte Lexware Office Hero Image](/public/img/og-image.png)

<p align="center">
  <h1 align="center">Laravel Lexware Office API Client</h1>
</p>

<p align="center">
  <a href="https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml"><img src="https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://codecov.io/github/pirabyte/laravel-lexware-office"><img src="https://codecov.io/github/pirabyte/laravel-lexware-office/branch/main/graph/badge.svg?token=KIpGNZLpn6" alt="codecov"></a>
</p>

<p align="center">Laravel package für die Lexware Office API.</p>

## Installation

```bash
composer require pirabyte/laravel-lexware-office
```

```bash
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office-config"
```

Der Service Provider registriert die folgenden Tags:
```bash
  * lexware-office-config: Nur für die Konfigurationsdatei.
  * lexware-office-migration: Nur für die Migrationsdatei.
  * lexware-office: Für die Konfigurations- und Migrationsdatei zusammen.
```
## Verwendung

### Mit Facade (Standard)

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// API-Methoden nutzen
$contact = LexwareOffice::contacts()->get('kontakt-id-hier');
```

### Direkte Instanzierung (z.B. für Multi-Tenant oder dynamische API-Keys)

```php
use Pirabyte\LaravelLexwareOffice\LexwareOffice;

// Instanz mit benutzerdefiniertem API-Key erstellen
$client = new LexwareOffice(
    'https://api.lexoffice.de/', 
    'Ihr-API-Key-Hier' // z.B. aus Benutzereinstellungen oder Datenbank
);

// API-Methoden nutzen
$contact = $client->contacts()->get('kontakt-id-hier');
```

## Features

- Strong Typed API-Methoden
- Automatisches Rate-Limiting (50 Anfragen pro Minute)
- Auto-Paging Iterator für effiziente Paginierung

## Rate-Limiting

Die Lexware Office API limitiert die Anzahl der Anfragen auf 50 pro Minute. Dieses Package implementiert automatisch ein Rate-Limiting, um die API-Grenzen einzuhalten und 429 Too Many Requests Fehler zu vermeiden.

```php
// Standardmäßig sind 50 Anfragen pro Minute erlaubt
$lexwareOffice = app('lexware-office');

// Optional: Benutzerdefiniertes Rate-Limit setzen
$lexwareOffice->setRateLimit(10); // Beschränkt auf 10 Anfragen pro Minute
```

Wenn das Rate-Limit erreicht wird, wird eine `LexwareOfficeApiException` mit dem Statuscode 429 und einer entsprechenden Fehlermeldung geworfen, die angibt, wie lange gewartet werden muss, bevor die nächste Anfrage gesendet werden kann.

## Implementierte API-Endpunkte

### Kontakte
- Kontakt erstellen
- Kontakt abrufen
- Kontakte auflisten
- Kontakt aktualisieren

### Belege
- Beleg erstellen
- Beleg abrufen
- Belege auflisten
- Beleg aktualisieren
- Beleg-Dokument generieren

### Länder
- Länder auflisten

### Finanzkonten
- Finanzkonto abrufen
- Finanzkonten filtern
- Finanzkonto löschen

### Finanztransaktionen
- Transaktion abrufen
- Transaktion aktualisieren
- Transaktion löschen
- Neueste Transaktionen abrufen
- Belegzuweisungen abrufen

### Transaktionszuweisungshinweise
- Transaktionszuweisungshinweis erstellen
