# Changelog (DE)

Alle nennenswerten Änderungen werden in dieser Datei dokumentiert.

## [1.4.0] - 2026-07-21 — Warenkorb kategoriegenau + Zweitpreis auf Slidern und Cross-Selling

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. Kein Schema-Break, keine Migration.

### Behoben

- **Der Warenkorb zeigte den Zweitpreis für alle Positionen, sobald das Plugin global aktiv war** — unabhängig davon, ob die Kategorie des Produkts den Zweitpreis überhaupt aktiviert hat. Produktdetail und Kacheln respektierten die Kategorie-Aktivierung, der Warenkorb nicht. Das Kategorie-Flag wird jetzt beim Aufbau des Warenkorbs je Position ermittelt und reist in Warenkorb, Checkout und Bestellung; die Anzeige folgt ihm. Bestehende Bestellungen behalten ihr bisheriges Verhalten.
- **Kein Zweitpreis auf CMS-Produkt-Slidern und im Cross-Selling („Kunden kauften auch")** — obwohl auf normalen Kacheln derselben Kategorie vorhanden. Ursache: den Produkten dieser Flächen fehlte die Kategorie-Zuordnung, auf der die Anzeige beruht. Slider und Cross-Selling laden die Kategorie jetzt mit und werden angereichert.
- **Kein Zweitpreis auf der Wunschliste** — die Wunschlisten-Karten zeigten ihn nicht, obwohl die Produkte in geflaggten Kategorien liegen. Wunschliste (Gast und angemeldet) lädt die Kategorie jetzt mit und wird angereichert.

## [1.3.3] - 2026-07-20 — Zweitpreis-Korrektheit: UVP, Steuersatz, Rundung

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. Kein Schema-Break, keine Migration.

### Behoben

- **UVP-Streichpreis (Zweitpreis) erscheint jetzt tatsächlich:** Auf Produktdetailseite und in den Produktkacheln setzte der Shopware-Core `isListPrice`/`listPrice` per `{% set %}` innerhalb des überschriebenen Blocks — diese Variablen sind nach `{{ parent() }}` im überschreibenden Block nicht mehr sichtbar (`is defined` = false), sodass der durchgestrichene UVP-Zweitpreis **nie** gerendert wurde. Der Wert wird jetzt im Kind-Block aus dem verfügbaren Preis-Objekt (`price`/`real`) selbst abgeleitet.
- **Steuersatz aus dem tatsächlich angewandten Wert statt dem Basissatz:** Detailseite, Kacheln und Staffelpreis-Zeilen nutzten `product.tax.taxRate` (den Basissatz der Steuergruppe). Das ist falsch bei länderabhängigen Steuerregeln und in steuerfreien Kontexten. Jetzt wird — wie im bereits korrekten Warenkorb-Pfad — der real angewandte Satz aus `calculatedPrice.calculatedTaxes` gelesen; bei Staffelpreisen je Staffel.
- **Netto/Brutto-Umrechnung deterministisch gerundet:** Die Umrechnung lag un-getestet und un-gerundet inline im Twig (Rundung nur implizit über die Anzeige) → mögliche Sub-Cent-Abweichung. Sie liegt jetzt im getesteten PHP-Service `DualPriceCalculator` (auf 2 Nachkommastellen gerundet); für steuerfreie/unbekannte Steuer-States und Satz 0 liefert er bewusst „nichts".

### Geändert

- Interne Codepflege ohne funktionale Auswirkung (Performance-Feinschliff bei der Preisdarstellung, zusätzliche Tests).

## [1.3.2] - 2026-07-20

> **Deployment:** `php bin/console cache:clear`.

### Behoben

- **Steuerfreie Kontexte:** Bei `tax-free` wird kein Zweitpreis mehr fabriziert (vorher wurde auf einen steuerfreien Preis fälschlich ein Brutto-Wert addiert) — nur die echten Steuer-States `gross`/`net` erzeugen einen Gegenpreis.
- **Währungssymbol im Warenkorb:** Der Zweitpreis nutzt jetzt bei fehlendem ISO-Code die Kontextwährung des Core-`currency`-Filters (vorher machte `|default` aus `null` einen Leerstring, was ein leeres/falsches Währungssymbol ergeben konnte).

### Geändert

- **Performance:** Die `categories`-Association wird im Autocomplete-Dropdown (Suggest) nicht mehr geladen — dort wird kein Zweitpreis gerendert.
- **Regressions-Sperre:** Contract-Test nagelt die drei Storefront-Overrides (sechs Blocknamen) gegen den Core fest.

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
