# RcDualPrice

Shopware 6 Plugin — zeigt neben dem regulären Preis den Zweitpreis (Netto bzw. Brutto) an.

---

## Was das Plugin macht

Shopware zeigt standardmäßig entweder Brutto- oder Nettopreise. Dieses Plugin ergänzt automatisch den jeweils fehlenden Preis — bei Brutto-Shops den Nettopreis und umgekehrt.

Die Aktivierung erfolgt pro Kategorie über ein Custom Field. Alle Produkte in einer aktivierten Kategorie erhalten den Zweitpreis.

---

## Unterstützte Kontexte

| Kontext | Beschreibung |
|---------|-------------|
| Produktdetailseite | Einzelpreis + UVP-Zweitpreis |
| Staffelpreise | Zweitpreis-Spalte in der Staffelpreis-Tabelle |
| Listing-Boxen | Alle Varianten (Standard, Image, Minimal, Wishlist) |
| CMS-Seiten | Produkt-Slider, -Boxen, -Listings, Cross-Selling |
| Suchergebnisse | Über Listing-Boxen |
| Warenkorb | Einzel- und Gesamtpreis im Off-Canvas-Cart und auf der Warenkorb-Seite |
| Checkout | Einzel- und Gesamtpreis auf der Bestätigungsseite |
| Bestellhistorie | Einzel- und Gesamtpreis in der Bestellübersicht |

### Sonderfälle

- **UVP/Listenpreis:** Durchgestrichener Zweitpreis neben dem aktuellen Zweitpreis
- **"Ab"-Preise:** Prefix bei Varianten mit unterschiedlichen Preisen
- **0% Steuersatz:** Kein Zweitpreis (identisch mit Erstpreis)

---

## Voraussetzungen

- Shopware 6.7 oder 6.8
- PHP 8.2+

---

## Installation

```bash
php bin/console plugin:refresh
php bin/console plugin:install --activate RcDualPrice
php bin/console cache:clear
```

---

## Konfiguration

### Plugin-Einstellungen

Im Admin unter **Einstellungen → Plugins → RcDualPrice**:

| Feld | Beschreibung |
|------|-------------|
| Plugin aktiv | Schaltet das gesamte Plugin an/aus |
| Textfarbe | Farbe des Zweitpreis-Texts (Colorpicker) |
| Schriftgröße | Klein / Normal / Groß |
| Schriftgewicht | Normal / Fett |
| Oberer Abstand | Abstand über dem Zweitpreis in Pixeln |

### Aktivierung per Kategorie

Im Admin unter **Kategorien → [Kategorie] → Individuelle Felder**:

| Feld | Beschreibung |
|------|-------------|
| Dual Price aktiv | Aktiviert die zweite Preisanzeige für alle Produkte in dieser Kategorie |

**Hinweis:** Im Warenkorb und Checkout wird der Zweitpreis bei allen Produkten angezeigt (unabhängig von der Kategorie), da dort keine Kategorie-Zuordnung verfügbar ist.

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

Entwickelt von [Ruhrcoder](https://ruhrcoder.de)
