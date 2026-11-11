# Changelog

## 0.5.1 (unveröffentlicht)

- Kontakte behalten beim Erstellen und Aktualisieren ihre gesendeten Rollen und Felder, wenn das anschließende Nachladen fehlschlägt. Antwortfelder haben Vorrang.
- `getCustomerNumber()` und `getVendorNumber()` liefern für Request-Rollen ohne serverseitig vergebene Nummer `null`. Die Nummer kann später per `get()` nachgeladen werden.

## 0.5.0

- Entwürfe bei `electronicDocument()` mit Status 409 von finalisierten Belegen ohne E-Rechnungsformat unterscheiden.
- E-Rechnungen profilabhängig über `electronicDocument()` laden; bei XML-404 für ZUGFeRD und Profil NONE verständliche Hinweise mit `ElectronicDocumentNotAvailableException` liefern.
- Bei leeren API-Fehlermeldungen HTTP-Status und Endpoint als Fallback ausgeben, ohne Query-Parameter oder Antwortinhalte zu übernehmen.
- Rechnungen und Rechnungskorrekturen mit typisierten Create-DTOs erstellen, lesen und direkt herunterladen.
- Finalisierung und Verknüpfung von Rechnungskorrekturen über POST-Query-Parameter unterstützen.
- Drucklayouts, Zahlungsinformationen und Event-Abonnements als typisierte Ressourcen bereitstellen.
- Dateien als PSR-7-Stream mit MIME-Typ und vorgeschlagenem Dateinamen herunterladen; PDF, XML oder Lexwares Standardformat anfordern.
- Enums für E-Rechnungsprofil, Belegstatus, Steuerart, Leistungsart, Positionstyp und Dateiformat ergänzen.
- Bestehende JSON-Aufrufe, Kontakte, XRechnung, OAuth2 und lokales Rate-Limit weiterverwenden.
