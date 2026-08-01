<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Cms;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Cms\ProductSliderCategoriesResolverDecorator;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class ProductSliderCategoriesResolverDecoratorTest extends TestCase
{
    public function testAddsCategoriesAssociationToProductCriteriaWhenActive(): void
    {
        $criteria = new Criteria();
        $collection = new CriteriaCollection();
        $collection->add('product-slider-entity_slot', ProductDefinition::class, $criteria);

        $decorator = $this->decorator($this->innerReturning($collection), activeValue: null);

        $decorator->collect($this->createMock(CmsSlotEntity::class), $this->createMock(ResolverContext::class));

        $this->assertArrayHasKey('categories', $criteria->getAssociations());
    }

    public function testDoesNotTouchCriteriaWhenPluginInactive(): void
    {
        $criteria = new Criteria();
        $collection = new CriteriaCollection();
        $collection->add('product-slider-entity_slot', ProductDefinition::class, $criteria);

        $decorator = $this->decorator($this->innerReturning($collection), activeValue: false);

        $decorator->collect($this->createMock(CmsSlotEntity::class), $this->createMock(ResolverContext::class));

        $this->assertSame([], $criteria->getAssociations());
    }

    public function testReturnsNullWhenInnerReturnsNull(): void
    {
        $decorator = $this->decorator($this->innerReturning(null), activeValue: null);

        $result = $decorator->collect($this->createMock(CmsSlotEntity::class), $this->createMock(ResolverContext::class));

        $this->assertNull($result);
    }

    public function testDelegatesGetType(): void
    {
        $inner = $this->createMock(CmsElementResolverInterface::class);
        $inner->method('getType')->willReturn('product-slider');

        $this->assertSame('product-slider', $this->decorator($inner, activeValue: null)->getType());
    }

    private function decorator(CmsElementResolverInterface $inner, mixed $activeValue): ProductSliderCategoriesResolverDecorator
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn($activeValue);

        return new ProductSliderCategoriesResolverDecorator($inner, new ConfigService($systemConfig));
    }

    private function innerReturning(?CriteriaCollection $collection): CmsElementResolverInterface
    {
        $inner = $this->createMock(CmsElementResolverInterface::class);
        $inner->method('collect')->willReturn($collection);

        return $inner;
    }
}
