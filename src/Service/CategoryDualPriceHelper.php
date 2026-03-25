<?php declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Service;

use Shopware\Core\Content\Category\CategoryEntity;

final class CategoryDualPriceHelper
{
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