<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Shopware\Core\Content\Category\CategoryEntity;

final class CategoryDualPriceHelperTest extends TestCase
{
    private CategoryDualPriceHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CategoryDualPriceHelper();
    }

    public function testReturnsFalseForNullCategory(): void
    {
        $this->assertFalse($this->helper->isCategoryEntityDualPriceActive(null));
    }

    public function testReturnsFalseWhenCustomFieldsMissing(): void
    {
        $category = new CategoryEntity();
        $category->setCustomFields(null);

        $this->assertFalse($this->helper->isCategoryEntityDualPriceActive($category));
    }

    public function testReturnsFalseWhenCustomFieldNotSet(): void
    {
        $category = new CategoryEntity();
        $category->setCustomFields(['some_other_field' => true]);

        $this->assertFalse($this->helper->isCategoryEntityDualPriceActive($category));
    }

    public function testReturnsTrueWhenCustomFieldActive(): void
    {
        $category = new CategoryEntity();
        $category->setCustomFields(['rc_dual_price_active' => true]);

        $this->assertTrue($this->helper->isCategoryEntityDualPriceActive($category));
    }

    public function testReturnsFalseWhenCustomFieldExplicitlyFalse(): void
    {
        $category = new CategoryEntity();
        $category->setCustomFields(['rc_dual_price_active' => false]);

        $this->assertFalse($this->helper->isCategoryEntityDualPriceActive($category));
    }
}
