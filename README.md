# Laravel Lexware Office API Client

Laravel package für die Lexware Office API.

[![Tests](https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml/badge.svg)](https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml)
[![codecov](https://codecov.io/github/pirabyte/laravel-lexware-office/branch/main/graph/badge.svg?token=KIpGNZLpn6)](https://codecov.io/github/pirabyte/laravel-lexware-office)
## Installation

```bash
composer require pirabyte/laravel-lexware-office
```

```bash
php artisan vendor:publish --provider="Pirabyte\LexwareOffice\LexwareOfficeServiceProvider" --tag="config"
```

## Verwendung

```php

use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

// API-Methoden nutzen
$contact = LexwareOffice::contacts()->get('kontakt-id-hier');

```

## Features

- Strong Typed API-Methoden
- Automatisches Rate-Limiting (50 Anfragen pro Minute)

## Rate-Limiting

Die Lexware Office API limitiert die Anzahl der Anfragen auf 50 pro Minute. Dieses Package implementiert automatisch ein Rate-Limiting, um die API-Grenzen einzuhalten und 429 Too Many Requests Fehler zu vermeiden.

```php
// Standardmäßig sind 50 Anfragen pro Minute erlaubt
$lexwareOffice = app('lexware-office');

// Optional: Benutzerdefiniertes Rate-Limit setzen
$lexwareOffice->setRateLimit(10); // Beschränkt auf 10 Anfragen pro Minute
```

Wenn das Rate-Limit erreicht wird, wird eine `LexwareOfficeApiException` mit dem Statuscode 429 und einer entsprechenden Fehlermeldung geworfen, die angibt, wie lange gewartet werden muss, bevor die nächste Anfrage gesendet werden kann.

## API-Endpunkte

| Bereich | Endpunkt | Implementiert 
|---------|----------|--------------|
| **Kontakte** |  |  |
|  | Kontakt erstellen | ✅ |
|  | Kontakt abrufen | ✅ |
|  | Kontakte auflisten | ✅ |
|  | Kontakt aktualisieren | ✅ |
| **Rechnungen** |  |  |
|  | Rechnung erstellen | ❌ |
|  | Rechnung abrufen | ❌ |
|  | Rechnungen auflisten | ❌ |
|  | Rechnung aktualisieren | ❌ |
| **Angebote** |  |  |
|  | Angebot erstellen | ❌ |
|  | Angebot abrufen | ❌ |
|  | Angebote auflisten | ❌ |
| **Lieferscheine** |  |  |
|  | Lieferschein erstellen | ❌ |
|  | Lieferschein abrufen | ❌ |
|  | Lieferscheine auflisten | ❌ |
| **Bestellungen** |  |  |
|  | Bestellung erstellen | ❌ |
|  | Bestellung abrufen | ❌ |
|  | Bestellungen auflisten | ❌ |
| **Gutschriften** |  |  |
|  | Gutschrift erstellen | ❌ |
|  | Gutschrift abrufen | ❌ |
|  | Gutschriften auflisten | ❌ |
| **Artikel** |  |  |
|  | Artikel abrufen | ❌ |
|  | Artikel auflisten | ❌ |
| **Dateien** |  |  |
|  | Datei hochladen | ❌ |
|  | Datei abrufen | ❌ |
| **Buchungen** |  |  |
|  | Buchung erstellen | ❌ |
|  | Buchung abrufen | ❌ |
|  | Buchungen auflisten | ❌ |
| **Belege** |  |  |
|  | Beleg erstellen | ✅ |
|  | Beleg abrufen | ✅ |
|  | Belege auflisten | ✅ |
|  | Beleg aktualisieren | ✅ |
|  | Beleg-Dokument generieren | ✅ |
