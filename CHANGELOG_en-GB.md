# Changelog (EN)

All notable changes are documented in this file.

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

- **Net/gross counter price** is automatically shown alongside the main price. Gross-priced shops display the net price, net-priced shops display the gross price. Calculation uses the configured tax rate.
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
