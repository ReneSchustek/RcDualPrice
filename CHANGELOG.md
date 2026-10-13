# Changelog (DE)

Alle nennenswerten Änderungen werden in dieser Datei dokumentiert.

## [1.4.4] - 2026-10-13: „Netto“ beim Streichpreis nur einmal

> **Deployment:** `php bin/console plugin:update RcDualPrice`, `php bin/console cache:clear`.

### Behoben

- **Streichpreis mit doppelter Beschriftung.** Bei Artikeln mit Listenpreis stand auf Produktseite und
  Produktkarte „~~Netto: 19,60 €~~ Netto: 17,64 €“. Jetzt steht die Beschriftung einmal da, und nur der alte
  Betrag ist durchgestrichen: „Netto: ~~19,60 €~~ 17,64 €“. Das gilt im alten Theme wie im Theme 2027, egal ob
  brutto oder netto angezeigt wird.

## [1.4.3] - 2026-10-09: Die Zusatzfelder heißen wie die Erweiterung

### Geändert

- Unter „Zusatzfelder“ an der Kategorie stand „Zweitpreis Einstellungen“. Dort steht jetzt „Zweitpreis (Netto/Brutto)“, also derselbe Name wie unter Erweiterungen. Ein bestehendes Set behielt beim Update bisher seinen alten Namen, weil die Installation bei vorhandenem Set nichts tat. Inzwischen gleicht sie die Beschriftung dabei ab.

## [1.4.2] - 2026-08-10: Vorbereitet auf die nächste Shopware-Hauptversion

### Geändert

- **Vorbereitung auf die nächste Shopware-Hauptversion.** Der Zugriff auf Suchergebnisse folgt der Schreibweise, die Shopware 6.8 verlangt. Am Verhalten ändert sich nichts.

## [1.4.1] - 2026-08-03: Lebenszyklus scheitert laut statt still

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. Kein Schema-Break, keine Migration.

### Behoben

- **Ein fehlender Container beim Installieren lief in einen irreführenden PHP-Fehler.** Die Absicherung dagegen war wirkungslos. Sie fragte den Container auf `null` ab, doch die Eigenschaft ist typisiert und hat keine Vorbelegung, also brach schon der Zugriff darauf ab. Die Meldung zeigte dann in Symfonys Bundle-Klasse statt auf die Aufrufreihenfolge. Jetzt greift die Prüfung vorher und nennt die Ursache beim Namen.

### Sonstiges

- Der Lebenszyklus (Installieren, Aktualisieren, Deinstallieren) und die Produktdetailseite sind jetzt durch Tests abgedeckt. Beides lief zuvor ungeprüft: der Lebenszyklus, weil er nur einmal ausgeführt wird, die Produktdetailseite, obwohl sie die Hauptfläche des Plugins ist.

## [1.4.0] - 2026-07-21: Warenkorb kategoriegenau, Zweitpreis auf Slidern und im Cross-Selling

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. Kein Schema-Break, keine Migration.

### Behoben

- **Der Warenkorb zeigte den Zweitpreis für alle Positionen, sobald das Plugin global aktiv war.** Ob die Kategorie des Produkts den Zweitpreis überhaupt aktiviert hatte, spielte dort keine Rolle. Produktdetail und Kacheln respektierten die Aktivierung an der Kategorie, der Warenkorb nicht. Das Kategorie-Flag wird jetzt beim Aufbau des Warenkorbs je Position ermittelt und reist durch Warenkorb, Checkout und Bestellung; die Anzeige richtet sich danach. Bestehende Bestellungen behalten ihr bisheriges Verhalten.
- **Kein Zweitpreis auf CMS-Produkt-Slidern und im Cross-Selling („Kunden kauften auch").** Auf normalen Kacheln derselben Kategorie war er da. Den Produkten dieser Flächen fehlte die Kategorie-Zuordnung, auf der die Anzeige beruht. Slider und Cross-Selling laden die Kategorie jetzt mit und werden angereichert.
- **Kein Zweitpreis auf der Wunschliste.** Die Karten der Wunschliste zeigten ihn nicht, obwohl die Produkte in geflaggten Kategorien liegen. Die Wunschliste (für Gäste und angemeldete Kunden) lädt die Kategorie jetzt mit und wird angereichert.

## [1.3.3] - 2026-07-20: Zweitpreis-Korrektheit bei UVP, Steuersatz und Rundung

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. Kein Schema-Break, keine Migration.

### Behoben

- **Der UVP-Streichpreis (Zweitpreis) erscheint jetzt tatsächlich.** Auf Produktdetailseite und in den Produktkacheln setzte der Shopware-Core `isListPrice`/`listPrice` per `{% set %}` innerhalb des überschriebenen Blocks. Nach `{{ parent() }}` sind diese Variablen im überschreibenden Block aber nicht mehr sichtbar (`is defined` = false), und so wurde der durchgestrichene UVP-Zweitpreis **nie** gerendert. Der Wert wird jetzt im Kind-Block aus dem verfügbaren Preis-Objekt (`price`/`real`) selbst abgeleitet.
- **Der Steuersatz kommt aus dem tatsächlich angewandten Wert statt aus dem Basissatz.** Detailseite, Kacheln und Staffelpreis-Zeilen nutzten `product.tax.taxRate`, den Basissatz der Steuergruppe. Bei länderabhängigen Steuerregeln und in steuerfreien Kontexten ist das falsch. Wie im Warenkorb-Pfad, der schon korrekt arbeitete, wird jetzt der real angewandte Satz aus `calculatedPrice.calculatedTaxes` gelesen, bei Staffelpreisen je Staffel.
- **Die Netto/Brutto-Umrechnung wird deterministisch gerundet.** Bisher stand sie ungetestet und ungerundet inline im Twig, gerundet wurde nur implizit über die Anzeige. Abweichungen unter einem Cent waren damit möglich. Die Umrechnung liegt jetzt im getesteten PHP-Service `DualPriceCalculator` und rundet auf 2 Nachkommastellen. Für steuerfreie oder unbekannte Steuer-States und für Satz 0 liefert sie bewusst „nichts".

### Geändert

- Interne Codepflege ohne funktionale Auswirkung (Feinschliff an der Performance der Preisdarstellung, zusätzliche Tests).

## [1.3.2] - 2026-07-20

> **Deployment:** `php bin/console cache:clear`.

### Behoben

- **Steuerfreie Kontexte:** Bei `tax-free` wird kein Zweitpreis mehr fabriziert. Vorher wurde auf einen steuerfreien Preis fälschlich ein Brutto-Wert addiert; einen Gegenpreis erzeugen nur die echten Steuer-States `gross`/`net`.
- **Währungssymbol im Warenkorb:** Fehlt der ISO-Code, nutzt der Zweitpreis jetzt die Kontextwährung des Core-Filters `currency`. Vorher machte `|default` aus `null` einen Leerstring, was ein leeres oder falsches Währungssymbol ergeben konnte.

### Geändert

- **Performance:** Im Autocomplete-Dropdown (Suggest) wird die Association `categories` nicht mehr geladen, weil dort kein Zweitpreis gerendert wird.
- **Regressions-Sperre:** Ein Contract-Test nagelt die drei Storefront-Overrides (sechs Blocknamen) gegen den Core fest.

## [1.3.1] - 2026-06-27

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build.

### Behoben
- **Exception-Chain im `CustomFieldInstaller`:** Fehler bei Install und Uninstall werden jetzt als
  `RuntimeException` mit dem ursprünglichen Fehler als `previous` weitergereicht, statt blank per
  `throw`. Der Lifecycle bricht weiterhin korrekt ab, für die Diagnose bleibt aber der vollständige
  Stacktrace erhalten.
- Kosmetische Code-Formatierung in `PageSubscriber` und `RcDualPrice` an den Projekt-Standard angeglichen.

## [1.3.0] - 2026-05-11

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build (Änderungen an PHP und Tests).

### Hinzugefügt
- **Strukturiertes Logging im `CustomFieldInstaller`** (PSR-3, Log-Context `ruhrcoder_dual_price.custom_field_installer`). Die Lifecycle-Pfade (Install/Update/Uninstall) loggen jetzt `info`- und `error`-Events mit Set-Namen, Feld-Namen und gegebenenfalls Exception-Klasse samt Message. Fehler im DAL, die bisher still verliefen, sind damit nachvollziehbar, und wer den Betrieb verantwortet, findet die Ursache, ohne den Code durchgehen zu müssen. Der Logger ist optional (`NullLogger` als Default), damit Test- und Lifecycle-Kontexte ohne Log-Infrastruktur weiterhin funktionieren.
- **`CustomFieldInstallerTest`** (8 Unit-Tests) mit Mock-Repository und Mock-Logger. Abgedeckt sind der Vertrag der Upsert-Payload (Set-Name, DE/EN-Labels, Field-Name/-Typ, Relation auf `category`), Idempotenz, Uninstall als No-op bei fehlendem Set, der Fehler-Pfad mit Logging und Rethrow sowie die Konstruktion mit NullLogger als Default.
- **`PageSubscriberTest`** um vier Tests für CMS-Seiten erweitert: Skip bei inaktivem Plugin, No-op bei leerer CmsPageCollection, Anreicherung eines Produkts aus `ProductBoxStruct` über `getProduct()` und eine robuste Behandlung von Slots ohne Daten und Sections=null.

### Geändert
- `PageSubscriber::onCmsPageLoaded` mit Block-Kommentar dokumentiert (Section → Block → Slot → Data, drei Datenquellen: `getProduct`, `getProducts`, `getListing`).

## [1.2.0] - 2026-05-11

> **Stand-Datum:** Erster gepflegter Changelog-Eintrag. Vorhergehende Versionen wurden nicht separat dokumentiert; der folgende Eintrag beschreibt deshalb den vollständigen Funktionsumfang von 1.2.0.

### Funktionsumfang

- **Netto-/Brutto-Gegenpreis:** Er erscheint automatisch zusätzlich zum Hauptpreis. In Brutto-Shops ist das der Nettopreis, in Netto-Shops der Bruttopreis. Berechnet wird anhand des hinterlegten Steuersatzes.
- **Aktivierung pro Kategorie** über ein Custom Field (`Dual Price aktiv`). Alle Produkte in einer aktivierten Kategorie erhalten die Anzeige.
- **Anzeige-Kontexte:** Produktdetailseite (inkl. Streichpreis bei UVP), Staffelpreise (zusätzliche Spalte), Listing-Boxen (Standard, Image, Minimal, Wishlist), CMS-Seiten (Slider, Boxen, Listings, Cross-Selling), Suchergebnisse, Warenkorb (Off-Canvas und Seite), Checkout-Bestätigung, Bestellhistorie.
- **Sonderfälle:** Durchgestrichener Netto-/Brutto-Streichpreis bei UVP, „Ab"-Prefix bei Varianten mit unterschiedlichen Preisen, keine Anzeige bei 0 % Steuersatz (Netto und Brutto sind identisch).
- **Plugin-Einstellungen:** Textfarbe (Colorpicker), Schriftgröße (Klein/Normal/Groß), Schriftgewicht (Normal/Fett), oberer Abstand in Pixeln. Dazu der Hauptschalter „Plugin aktiv".
- **Warenkorb und Checkout:** Dort erscheint der Gegenpreis bei allen Produkten, unabhängig von der Kategorie, weil die Kategorie-Zuordnung im Cart-Kontext nicht verfügbar ist.

### Qualitätsstand

- PHP CS Fixer (PSR-12): 0 Violations
- Composer-Audit ohne Befund
- CI-Pipeline in GitHub Actions aktiv (Push auf `main` und Pull Requests)

### Bekannte Einschränkungen

- Suchvorschläge (Suggest-Dropdown) zeigen keinen Gegenpreis. Das ist eine bewusste Entscheidung, weil die Oberfläche dort kompakt bleiben soll.

---

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
