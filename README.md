# RcDualPrice

Shopware 6 Plugin — zeigt auf Produktseiten und in Listings einen zweiten Preis an.

---

## Was das Plugin macht

Manche Shops zeigen neben dem regulären Preis einen weiteren Preis an — z. B. einen alten Preis, einen Staffelpreis oder einen brutto/netto Vergleich. Shopware-Standard unterstützt dies nicht nativ für alle Darstellungsformen.

Dieses Plugin liest pro Kategorie ein Custom Field (`rc_dual_price_active`) und reichert alle Produkte in dieser Kategorie mit einer `rc_dual_price_active`-Extension an. Das Twig-Template kann diese Extension auswerten und den zweiten Preis darstellen. Die Aktivierung erfolgt also per Kategorie, nicht per einzelnem Produkt.

Der zweite Preis und sein CSS-Styling werden über das Plugin global konfiguriert.

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
| Dual Price aktivieren | Schaltet das gesamte Plugin an/aus |
| CSS-Styles | Optionale CSS-Anpassungen für die Preisdarstellung |

### Aktivierung per Kategorie

Im Admin unter **Kategorien → [Kategorie] → Individuelle Felder**:

| Feld | Beschreibung |
|------|-------------|
| Dual Price aktiv | Aktiviert die zweite Preisanzeige für alle Produkte in dieser Kategorie |

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
