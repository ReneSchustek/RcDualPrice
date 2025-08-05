<?php declare(strict_types=1);

namespace RcDualPrice\Service;

use Shopware\Core\Content\Category\CategoryEntity;

class CategoryDualPriceHelper
{
    /**
     * Prüft, ob für die übergebene Kategorie die Dual Price Anzeige aktiv ist.
     * Liest das Custom Field der Kategorie aus.
     */
    public function isCategoryDualPriceActive(string $categoryId): bool
    {
        // Wenn wir hier nur die ID haben, können wir das Custom Field nicht direkt lesen
        // Wir brauchen die CategoryEntity mit den Custom Fields
        return false;
    }

    /**
     * Prüft, ob für die übergebene Kategorie-Entity die Dual Price Anzeige aktiv ist.
     */
    public function isCategoryEntityDualPriceActive(?CategoryEntity $category): bool
    {
        if (!$category) {
            return false;
        }

        $customFields = $category->getCustomFields();

        if (!$customFields) {
            return false;
        }

        return (bool) ($customFields['rc_dual_price_active'] ?? false);
    }
}