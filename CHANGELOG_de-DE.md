# Changelog (DE)

Alle nennenswerten Änderungen werden in dieser Datei dokumentiert.

## [1.3.1] - 2026-06-27

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build.

### Behoben
- **Exception-Chain im `CustomFieldInstaller`:** Install- und Uninstall-Fehler werden jetzt als
  `RuntimeException` mit dem ursprünglichen Fehler als `previous` weitergereicht (statt blankem
  `throw`). Der Lifecycle bricht weiterhin korrekt ab, aber der vollständige Stacktrace bleibt für
  die Diagnose erhalten.
- Kosmetische Code-Formatierung in `PageSubscriber` und `RcDualPrice` an den Projekt-Standard angeglichen.

## [1.3.0] - 2026-05-11

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build (PHP- und Test-Änderungen).

### Hinzugefügt
- **Strukturiertes Logging im `CustomFieldInstaller`** (PSR-3, Log-Context `ruhrcoder_dual_price.custom_field_installer`). Lifecycle-Pfade (Install/Update/Uninstall) loggen jetzt `info`/`error`-Events mit Set-Namen, Feld-Namen und gegebenenfalls Exception-Klasse + Message. Bisherige Silent-Failures bei DAL-Fehlern sind ab sofort nachvollziehbar; Ops-Team kann Root-Cause ohne Code-Walkthrough finden. Logger ist optional (`NullLogger` als Default), damit Test- und Lifecycle-Kontexte ohne Log-Infrastruktur weiterhin funktionieren.
- **`CustomFieldInstallerTest`** (8 Unit-Tests) mit Mock-Repository und Mock-Logger: Upsert-Payload-Vertrag (Set-Name, DE/EN-Labels, Field-Name/-Typ, Relation auf `category`), Idempotenz, Uninstall-No-op bei abwesendem Set, Fehler-Pfad mit Logging + Rethrow, NullLogger-Default-Konstruktion.
- **`PageSubscriberTest`** um vier CMS-Page-Tests erweitert: Skip bei inaktivem Plugin, No-op bei leerer CmsPageCollection, Enrichment eines Produkts aus `ProductBoxStruct` über `getProduct()`, robuste Behandlung von Slots ohne Daten und Sections=null.

### Geändert
- `PageSubscriber::onCmsPageLoaded` mit Block-Kommentar dokumentiert (Section → Block → Slot → Data, drei Datenquellen: `getProduct`, `getProducts`, `getListing`).

## [1.2.0] - 2026-05-11

> **Stand-Datum:** Erster gepflegter Changelog-Eintrag. Vorhergehende Versionen wurden nicht separat dokumentiert — der nachfolgende Eintrag beschreibt den vollständigen Funktionsumfang der 1.2.0.

### Funktionsumfang

- **Netto-/Brutto-Gegenpreis** wird automatisch zusätzlich zum Hauptpreis angezeigt. In Brutto-Shops erscheint der Nettopreis, in Netto-Shops der Bruttopreis. Berechnung anhand des hinterlegten Steuersatzes.
- **Aktivierung pro Kategorie** über ein Custom Field (`Dual Price aktiv`). Alle Produkte in einer aktivierten Kategorie erhalten die Anzeige.
- **Anzeige-Kontexte:** Produktdetailseite (inkl. Streichpreis bei UVP), Staffelpreise (zusätzliche Spalte), Listing-Boxen (Standard, Image, Minimal, Wishlist), CMS-Seiten (Slider, Boxen, Listings, Cross-Selling), Suchergebnisse, Warenkorb (Off-Canvas + Seite), Checkout-Bestätigung, Bestellhistorie.
- **Sonderfälle:** Durchgestrichener Netto-/Brutto-Streichpreis bei UVP, „Ab"-Prefix bei Varianten mit unterschiedlichen Preisen, keine Anzeige bei 0 %-Steuersatz (Netto und Brutto sind identisch).
- **Plugin-Einstellungen:** Textfarbe (Colorpicker), Schriftgröße (Klein/Normal/Groß), Schriftgewicht (Normal/Fett), oberer Abstand in Pixeln. Master-Schalter „Plugin aktiv".
- **Warenkorb und Checkout:** Gegenpreis erscheint dort bei allen Produkten unabhängig von der Kategorie (Kategorie-Zuordnung ist im Cart-Kontext nicht verfügbar).

### Qualitätsstand

- PHP CS Fixer (PSR-12) — 0 Violations
- Composer-Audit clean
- GitHub Actions CI-Pipeline aktiv (Push auf `main` + Pull Requests)

### Bekannte Einschränkungen

- Suchvorschläge (Suggest-Dropdown) zeigen keinen Gegenpreis — bewusste Entscheidung wegen kompakter UI.

---

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
