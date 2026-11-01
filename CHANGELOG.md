# Changelog

## 0.5.0 (unveröffentlicht)

- E-Rechnungen profilabhängig über `electronicDocument()` laden; bei XML-404 für ZUGFeRD und Profil NONE verständliche Hinweise mit `ElectronicDocumentNotAvailableException` liefern.
- Bei leeren API-Fehlermeldungen HTTP-Status und Endpoint als Fallback ausgeben, ohne Query-Parameter oder Antwortinhalte zu übernehmen.
- Rechnungen und Rechnungskorrekturen mit typisierten Create-DTOs erstellen, lesen und direkt herunterladen.
- Finalisierung und Verknüpfung von Rechnungskorrekturen über POST-Query-Parameter unterstützen.
- Drucklayouts, Zahlungsinformationen und Event-Abonnements als typisierte Ressourcen bereitstellen.
- Dateien als PSR-7-Stream mit MIME-Typ und vorgeschlagenem Dateinamen herunterladen; PDF, XML oder Lexwares Standardformat anfordern.
- Enums für E-Rechnungsprofil, Belegstatus, Steuerart, Leistungsart, Positionstyp und Dateiformat ergänzen.
- Bestehende JSON-Aufrufe, Kontakte, XRechnung, OAuth2 und lokales Rate-Limit weiterverwenden.

Die Veröffentlichung erfolgt nach Review. Diese Erweiterung enthält keinen Envoix-App-Workflow.
