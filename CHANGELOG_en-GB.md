# Changelog (EN)

All notable changes are documented in this file.

## [1.4.1] - 2026-08-03 — Lifecycle fails loudly instead of silently

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. No schema break, no migration.

### Fixed

- **A missing container during installation ran into a misleading PHP error.** The guard against it had no effect: it compared the container to `null`, but the property is typed and has no default — merely accessing it aborted, with a message pointing into Symfony's bundle class instead of at the lifecycle call order. The check now runs before that access and names the actual cause.

### Other

- The lifecycle (install, update, uninstall) and the product detail page are now covered by tests. Both ran unverified before: the lifecycle because it only executes once, the product detail page even though it is the plugin's main surface.

## [1.4.0] - 2026-07-21 — Category-accurate in the cart plus second price on sliders and cross-selling

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. No schema break, no migration.

### Fixed

- **The cart showed the second price for every line item as soon as the plugin was globally active** — regardless of whether the product's category had enabled the second price at all. Product detail and cards respected the category activation, the cart did not. The category flag is now resolved per line item while the cart is built and travels into cart, checkout and order; the display follows it. Existing orders keep their previous behaviour.
- **No second price on CMS product sliders and in cross-selling ("customers also bought")** — even though it was present on regular cards of the same category. Cause: the products on these surfaces lacked the category association the display relies on. Sliders and cross-selling now load the category and are enriched.
- **No second price on the wishlist** — the wishlist cards did not show it, even though the products are in flagged categories. The wishlist (guest and logged-in) now loads the category and is enriched.

## [1.3.3] - 2026-07-20 — Second-price correctness: list price, tax rate, rounding

> **Deployment:** `php bin/console plugin:update RcDualPrice && php bin/console cache:clear`. No schema break, no migration.

### Fixed

- **The list-price (RRP) strike-through second price now actually renders:** On the product detail page and in product cards, the Shopware core set `isListPrice`/`listPrice` via `{% set %}` inside the overridden block — those variables are not visible in the overriding block after `{{ parent() }}` (`is defined` = false), so the struck-through list-price second price was **never** rendered. It is now derived in the child block from the available price object (`price`/`real`).
- **Tax rate taken from the actually applied value instead of the base rate:** Detail page, cards and tier-price rows used `product.tax.taxRate` (the tax group's base rate), which is wrong under country-dependent tax rules and in tax-free contexts. It now reads the actually applied rate from `calculatedPrice.calculatedTaxes` — as the already-correct cart path does — per tier for staggered prices.
- **Net/gross conversion rounded deterministically:** The conversion lived untested and unrounded inline in Twig (rounding only implicit at display) → possible sub-cent drift. It now lives in the tested PHP service `DualPriceCalculator` (rounded to 2 decimals); for tax-free/unknown tax states and rate 0 it deliberately yields "nothing".

### Changed

- Internal code maintenance with no functional impact (minor performance tuning in the price rendering, additional tests).

## [1.3.2] - 2026-07-20

> **Deployment:** `php bin/console cache:clear`.

### Fixed

- **Tax-free contexts:** No secondary price is fabricated for `tax-free` any more (previously a gross value was wrongly added on top of a tax-free price) — only the real tax states `gross`/`net` produce a counter price.
- **Currency symbol in the cart:** With a missing ISO code the secondary price now falls back to the context currency of the core `currency` filter (previously `|default` turned `null` into an empty string, which could yield a missing/wrong currency symbol).

### Changed

- **Performance:** The `categories` association is no longer loaded for the autocomplete suggest (no secondary price is rendered there).
- **Regression guard:** A contract test pins the three storefront overrides (six block names) against the core.

## [1.3.1] - 2026-06-27

> **Deployment:** `php bin/console cache:clear`. No database migration, no storefront build.

### Fixed
- **Exception chain in `CustomFieldInstaller`:** install and uninstall failures are now rethrown as
  a `RuntimeException` carrying the original error as `previous` (instead of a bare `throw`). The
  lifecycle still aborts correctly, but the full stack trace is preserved for diagnosis.
- Cosmetic code formatting in `PageSubscriber` and `RcDualPrice` aligned to the project standard.

## [1.3.0] - 2026-05-11

> **Deployment:** `php bin/console cache:clear`. No database migration, no storefront build (PHP and test changes).

### Added
- **Structured logging in `CustomFieldInstaller`** (PSR-3, log context `ruhrcoder_dual_price.custom_field_installer`). Lifecycle paths (install/update/uninstall) now emit `info`/`error` events with set name, field name and exception class/message where applicable. Previously silent DAL failures are now traceable; ops teams can find the root cause without a code walkthrough. The logger is optional (`NullLogger` default) so tests and lifecycle contexts without log infrastructure keep working.
- **`CustomFieldInstallerTest`** (8 unit tests) with mocked repository and logger: upsert payload contract (set name, DE/EN labels, field name/type, `category` relation), idempotency, uninstall no-op for absent set, error path with logging + rethrow, NullLogger default construction.
- **`PageSubscriberTest`** extended with four CMS-page tests: skip when plugin inactive, no-op on empty CmsPageCollection, enrichment of a product from `ProductBoxStruct` via `getProduct()`, robust handling of slots without data and `sections === null`.

### Changed
- `PageSubscriber::onCmsPageLoaded` annotated with a block comment (Section → Block → Slot → Data; three data sources: `getProduct`, `getProducts`, `getListing`).

## [1.2.0] - 2026-05-11

> **As-of date:** First maintained changelog entry. Earlier versions were not separately documented — the entry below describes the complete feature set of 1.2.0.

### Feature set

- **Net/gross counter price** is automatically shown alongside the main price. Gross-priced shops display the net price, net-priced shops display the groß price. Calculation uses the configured tax rate.
- **Activation per category** via a custom field (`Dual Price active`). All products in an activated category receive the display.
- **Display contexts:** product detail page (including strikethrough price for RRP), tier prices (additional column), listing boxes (standard, image, minimal, wishlist), CMS pages (slider, boxes, listings, cross-selling), search results, cart (off-canvas + page), checkout confirmation, order history.
- **Edge cases:** strikethrough net/gross RRP price, „from" prefix for variants with different prices, no display at 0 % tax rate (net and gross are identical).
- **Plugin settings:** text colour (colour picker), font size (small/normal/large), font weight (normal/bold), top spacing in pixels. Master toggle „Plugin active".
- **Cart and checkout:** counter price appears for all products regardless of category (category assignment is not available in the cart context).

### Quality status

- PHP CS Fixer (PSR-12) — 0 violations
- Composer audit clean
- GitHub Actions CI pipeline active (push on `main` + pull requests)

### Known limitations

- Search suggestions (suggest dropdown) do not show a counter price — intentional decision for compact UI.

---

The format is based on [Keep a Changelog](https://keepachangelog.com/1.0.0/).
