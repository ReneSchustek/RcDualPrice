# RcDualPrice

[![CI](https://github.com/ReneSchustek/RcDualPrice/actions/workflows/ci.yml/badge.svg)](https://github.com/ReneSchustek/RcDualPrice/actions/workflows/ci.yml)

Shopware 6 Plugin — zeigt neben dem Hauptpreis automatisch den Netto- bzw. Bruttopreis an.

---

## Was das Plugin macht

Shopware zeigt standardmäßig entweder Brutto- oder Nettopreise. Dieses Plugin ergänzt automatisch den jeweils fehlenden Gegenpreis: In Brutto-Shops wird der Nettopreis angezeigt, in Netto-Shops der Bruttopreis.

Die Berechnung erfolgt anhand des hinterlegten Steuersatzes. Die Aktivierung wird pro Kategorie über ein Custom Field gesteuert — alle Produkte in einer aktivierten Kategorie erhalten die Netto-/Brutto-Anzeige.

---

## Wo wird der Gegenpreis angezeigt?

| Kontext | Beschreibung |
|---------|-------------|
| Produktdetailseite | Netto/Brutto unter dem Hauptpreis, bei UVP auch für den Streichpreis |
| Staffelpreise | Zusätzliche Netto-/Brutto-Spalte in der Staffelpreis-Tabelle |
| Listing-Boxen | Alle Varianten (Standard, Image, Minimal, Wishlist) |
| CMS-Seiten | Produkt-Slider, -Boxen, -Listings, Cross-Selling |
| Suchergebnisse | Über Listing-Boxen |
| Warenkorb | Einzel- und Gesamtpreis im Off-Canvas-Cart und auf der Warenkorb-Seite |
| Checkout | Einzel- und Gesamtpreis auf der Bestätigungsseite |
| Bestellhistorie | Einzel- und Gesamtpreis in der Bestellübersicht |

### Sonderfälle

- **UVP/Listenpreis:** Durchgestrichener Netto-/Brutto-Streichpreis neben dem aktuellen Gegenpreis
- **"Ab"-Preise:** "Ab"-Prefix bei Varianten mit unterschiedlichen Preisen
- **0% Steuersatz:** Keine Anzeige, da Netto und Brutto identisch sind

---

## Voraussetzungen

- Shopware 6.7 oder 6.8
- PHP 8.2+

---

## Installation

Das Plugin gehört als Ordner `custom/plugins/RcDualPrice` in die Shopware-Installation. Übertragen wird er von Hand — per FTP in dieses Verzeichnis kopieren; ein Composer-Paket gibt es nicht. Danach registriert Shopware ihn über die folgenden Befehle:

Als Archiv geht es auch ohne FTP: In der Administration unter **Erweiterungen → Meine Erweiterungen → Erweiterung hochladen** nimmt Shopware eine ZIP-Datei entgegen und legt sie selbst an die richtige Stelle; auf der Konsole tut `plugin:zip-import` dasselbe. Danach folgen dieselben Schritte wie unten. Wer das Archiv unter macOS packt, entfernt vorher den Ordner `__MACOSX` — sonst weist Shopware die Datei ab.

```bash
php bin/console plugin:refresh
php bin/console plugin:install --activate RcDualPrice
php bin/console cache:clear
```

**Aktualisierungen laufen denselben Weg.** Den neuen Ordner per FTP über den alten legen, danach `plugin:refresh` und `plugin:update RcDualPrice`. Eine Aktualisierung von selbst gibt es nicht — ohne Composer-Paket und ohne Shopware-Store bleibt sie Sache des Betreibers. Vor einem Sprung über eine Hauptversion gehört ein Datenbank-Abzug dazu.

---

## Konfiguration

### Plugin-Einstellungen

Im Admin unter **Einstellungen → Plugins → RcDualPrice**:

| Feld | Beschreibung |
|------|-------------|
| Plugin aktiv | Schaltet das gesamte Plugin an/aus |
| Textfarbe | Farbe des Netto-/Brutto-Texts (Colorpicker) |
| Schriftgröße | Klein / Normal / Groß |
| Schriftgewicht | Normal / Fett |
| Oberer Abstand | Abstand über dem Gegenpreis in Pixeln |

### Aktivierung per Kategorie

Im Admin unter **Kategorien → [Kategorie] → Individuelle Felder**:

| Feld | Beschreibung |
|------|-------------|
| Dual Price aktiv | Aktiviert die Netto-/Brutto-Anzeige für alle Produkte in dieser Kategorie |

**Hinweis:** Im Warenkorb und Checkout wird der Gegenpreis bei allen Produkten angezeigt (unabhängig von der Kategorie), da dort keine Kategorie-Zuordnung verfügbar ist.

---

## Update

```bash
php bin/console plugin:refresh
php bin/console plugin:update RcDualPrice
php bin/console cache:clear
```

---

## Entwicklung

```bash
composer install
composer quality   # cs-check + phpstan + test
```

---

## Lizenz

MIT — siehe `composer.json`.

---

Entwickelt von [Ruhrcoder](https://ruhrcoder.de)
