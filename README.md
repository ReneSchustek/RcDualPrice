# RcDualPrice

[![CI](https://github.com/ReneSchustek/RcDualPrice/actions/workflows/ci.yml/badge.svg)](https://github.com/ReneSchustek/RcDualPrice/actions/workflows/ci.yml)

Ein Plugin für Shopware 6, das neben dem Hauptpreis automatisch den Netto- bzw. Bruttopreis anzeigt.

---

## Was das Plugin macht

Von Haus aus zeigt Shopware entweder Brutto- oder Nettopreise. Das Plugin ergänzt automatisch den jeweils fehlenden Gegenpreis: In Brutto-Shops erscheint der Nettopreis, in Netto-Shops der Bruttopreis.

Gerechnet wird mit dem hinterlegten Steuersatz. Ob die Anzeige erscheint, steuert ein Custom Field an der Kategorie; alle Produkte in einer aktivierten Kategorie erhalten die Netto-/Brutto-Anzeige.

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

Das Plugin gehört als Ordner `custom/plugins/RcDualPrice` in die Shopware-Installation. Übertragen wird er von Hand, per FTP in dieses Verzeichnis; ein Composer-Paket gibt es nicht. Danach registriert Shopware ihn über die folgenden Befehle:

Als Archiv geht es auch ohne FTP: In der Administration unter **Erweiterungen → Meine Erweiterungen → Erweiterung hochladen** nimmt Shopware eine ZIP-Datei entgegen und legt sie selbst an die richtige Stelle; auf der Konsole tut `plugin:zip-import` dasselbe. Danach folgen dieselben Schritte wie unten. Wer das Archiv unter macOS packt, entfernt vorher den Ordner `__MACOSX`, sonst weist Shopware die Datei ab.

```bash
php bin/console plugin:refresh
php bin/console plugin:install --activate RcDualPrice
php bin/console cache:clear
```

**Aktualisierungen laufen denselben Weg.** Den neuen Ordner per FTP über den alten legen, danach `plugin:refresh` und `plugin:update RcDualPrice`. Von selbst aktualisiert sich nichts. Ohne Composer-Paket und ohne Shopware-Store bleibt das Sache des Betreibers. Vor einem Sprung über eine Hauptversion gehört ein Datenbank-Abzug dazu.

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

Im Admin an der Kategorie unter **Zusatzfelder → Zweitpreis (Netto/Brutto)**:

| Feld | Beschreibung |
|------|-------------|
| Zweitpreis-Anzeige für diese Kategorie aktivieren | Aktiviert die Netto-/Brutto-Anzeige für alle Produkte in dieser Kategorie |

**Hinweis:** Auch Warenkorb, Checkout und Bestellung richten sich nach der Kategorie. Beim Aufbau des Warenkorbs wird für jede Position festgehalten, ob ihre Kategorie den Gegenpreis aktiviert hat, und dieser Wert reist bis in die Bestellung mit. Bestellungen aus der Zeit vor 1.4.0 tragen ihn nicht; dort zeigt die Bestellung den Gegenpreis wie früher, sobald das Plugin global aktiv ist.

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

Proprietär – siehe `composer.json`.

---

Entwickelt von [Ruhrcoder](https://ruhrcoder.de)
