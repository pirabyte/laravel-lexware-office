# Laravel Lexware Office API Client

Laravel package für die Lexware Office API.

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

| Bereich | Endpunkt | Implementiert | Methode |
|---------|----------|--------------|---------|
| **Kontakte** |  |  |  |
|  | Kontakt erstellen | ✅ | `LexwareOffice::contacts()->create($data)` |
|  | Kontakt abrufen | ✅ | `LexwareOffice::contacts()->get($id)` |
|  | Kontakte auflisten | ✅ | `LexwareOffice::contacts()->list($filter)` |
|  | Kontakt aktualisieren | ✅ | `LexwareOffice::contacts()->update($id, $data)` |
| **Rechnungen** |  |  |  |
|  | Rechnung erstellen | ❌ |  |
|  | Rechnung abrufen | ❌ |  |
|  | Rechnungen auflisten | ❌ |  |
|  | Rechnung aktualisieren | ❌ |  |
| **Angebote** |  |  |  |
|  | Angebot erstellen | ❌ |  |
|  | Angebot abrufen | ❌ |  |
|  | Angebote auflisten | ❌ |  |
| **Lieferscheine** |  |  |  |
|  | Lieferschein erstellen | ❌ |  |
|  | Lieferschein abrufen | ❌ |  |
|  | Lieferscheine auflisten | ❌ |  |
| **Bestellungen** |  |  |  |
|  | Bestellung erstellen | ❌ |  |
|  | Bestellung abrufen | ❌ |  |
|  | Bestellungen auflisten | ❌ |  |
| **Gutschriften** |  |  |  |
|  | Gutschrift erstellen | ❌ |  |
|  | Gutschrift abrufen | ❌ |  |
|  | Gutschriften auflisten | ❌ |  |
| **Artikel** |  |  |  |
|  | Artikel abrufen | ❌ |  |
|  | Artikel auflisten | ❌ |  |
| **Dateien** |  |  |  |
|  | Datei hochladen | ❌ |  |
|  | Datei abrufen | ❌ |  |
| **Buchungen** |  |  |  |
|  | Buchung erstellen | ❌ |  |
|  | Buchung abrufen | ❌ |  |
|  | Buchungen auflisten | ❌ |  |