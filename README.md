![Laravel Lexware Office](public/img/og-image.png)

# Laravel Lexware Office

[![Tests](https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml/badge.svg)](https://github.com/pirabyte/laravel-lexware-office/actions/workflows/tests.yml)
[![Code coverage](https://codecov.io/github/pirabyte/laravel-lexware-office/branch/main/graph/badge.svg?token=KIpGNZLpn6)](https://codecov.io/github/pirabyte/laravel-lexware-office)

Laravel-Client für die Lexware Office API mit typisierten Modellen, OAuth2-Unterstützung und lokalem Rate-Limit. Unterstützt PHP ab 8.3 sowie Laravel 11 bis 13.

## Installation

```bash
composer require pirabyte/laravel-lexware-office
```

Trage deinen API-Key in `.env` ein:

```dotenv
LEXWARE_OFFICE_API_KEY=dein-api-key
```

Laravel lädt die Paketkonfiguration automatisch. Wenn du sie anpassen möchtest, veröffentliche sie mit:

```bash
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office-config"
```

Für die Speicherung von OAuth2-Tokens in der Datenbank kannst du zusätzlich die Migration mit `--tag="lexware-office-migration"` veröffentlichen. `--tag="lexware-office"` veröffentlicht Konfiguration und Migration zusammen.

## Verwendung

Mit dem konfigurierten API-Key nutzt du die Facade:

```php
use Pirabyte\LaravelLexwareOffice\Facades\LexwareOffice;

$contact = LexwareOffice::contacts()->get($contactId);
```

Für einen anderen API-Key erstellst du einen eigenen Client:

```php
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

$client = LexwareOfficeFactory::withApiKey($apiKey);
$contact = $client->contacts()->get($contactId);
```

Weitere Beispiele: [Kontakte](examples/contacts.md), [OAuth2](examples/oauth2-authentication.md), [Fehlerbehandlung](examples/error-handling.md) und [Partner-Integrationen](examples/partner-integrations.md).

## Implementierungsstand

Diese Ressourcen und Methoden sind derzeit im Paket implementiert:

| Bereich | Zugriff | Methoden |
| --- | --- | --- |
| Kontakte | `contacts()` | `create`, `get`, `update`, `filter`, `all`, `count`, `getAutoPagingIterator` |
| Belege | `vouchers()` | `create`, `createAndAttachFile`, `get`, `update`, `filter`, `all`, `document`, `downloadDocument`, `attachFile` |
| Länder | `countries()` | `all` |
| Finanzkonten | `financialAccounts()` | `create`, `get`, `filter`, `delete` |
| Finanztransaktionen | `financialTransactions()` | `create`, `get`, `update`, `delete`, `latest`, `getVoucherAssignments` |
| Buchungskategorien | `postingCategories()` | `get` |
| Profil | `profile()` | `get` |
| Transaktionszuweisungshinweise | `transactionAssignmentHints()` | `create` |
| Partner-Integrationen | `partnerIntegrations()` | `get`, `update` |

### Besonderheit bei der neuesten Finanztransaktion

`financialTransactions()->latest($financialAccountId)` kann bei einem bestehenden Finanzkonto ohne Transaktionen eine 404-Antwort von Lexware erhalten. Die Methode gibt nur bei einer erfolgreichen, leeren Antwort `null` zurück. Wenn du prüfen musst, ob das Konto existiert, nutze zusätzlich `financialAccounts()->get($financialAccountId)`.

## Rate-Limit

Der Client prüft vor Anfragen ein lokales Laravel Rate-Limit von standardmäßig 50 Anfragen pro Minute. Bei erreichtem Limit wirft er eine `LexwareOfficeApiException` mit Status 429. Das Limit kannst du in `config/lexware-office.php` über `max_requests_per_minute` oder am Client ändern:

```php
$client = app('lexware-office');
$client->setRateLimit(10);
```

Das lokale Limit garantiert keine freien Kapazitäten bei der Lexware Office API. Für Abläufe mit mehreren Anfragen bietet der Client `waitForRateLimitCapacity()` an.

## Im Einsatz bei

[Envoix](https://envoix.de/) nutzt dieses Paket für seine Lexware Office Anbindung. Die Anwendung bereitet Stripe-Zahlungen, Gebühren und Belege für Lexware Office vor. [So funktioniert die Stripe-Integration](https://envoix.de/stripe-lexware).

## Lizenz

[MIT](LICENSE)
