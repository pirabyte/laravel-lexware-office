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

## Feaures

- Strong Typed API-Methoden
- Automatisches Rate-Limiting

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
