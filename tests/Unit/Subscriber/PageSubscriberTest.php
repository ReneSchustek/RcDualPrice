<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Subscriber\PageSubscriber;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

final class PageSubscriberTest extends TestCase
{
    private ConfigService $configService;
    private CategoryDualPriceHelper $categoryHelper;
    private PageSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(ConfigService::class);
        $this->categoryHelper = $this->createMock(CategoryDualPriceHelper::class);
        $this->subscriber = new PageSubscriber($this->configService, $this->categoryHelper);
    }

    public function testGetSubscribedEventsReturnsArray(): void
    {
        $events = PageSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertCount(3, $events);
    }

    public function testOnListingResultSkipsWhenPluginInactive(): void
    {
        $this->configService->method('isDualPriceActive')->willReturn(false);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->expects($this->never())->method('getResult');

        $this->subscriber->onListingResult($event);
    }

    public function testOnListingResultEnrichesProductsWhenActive(): void
    {
        $this->configService->method('isDualPriceActive')->willReturn(true);
        $this->configService->method('getCssStyles')->willReturn('color: #6c757d;');

        $category = new CategoryEntity();
        $category->setId('cat-1');
        $category->setCustomFields(['rc_dual_price_active' => true]);

        $this->categoryHelper->method('isCategoryEntityDualPriceActive')
            ->with($category)
            ->willReturn(true);

        $product = new ProductEntity();
        $product->setId('prod-1');
        $categories = new CategoryCollection([$category]);
        $product->setCategories($categories);

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getElements')->willReturn(['prod-1' => $product]);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->method('getResult')->willReturn($result);

        $this->subscriber->onListingResult($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertTrue($extension->get('enabled'));
        $this->assertSame('color: #6c757d;', $extension->get('cssStyles'));
    }

    public function testEnrichProductSetsEnabledFalseWithoutMatchingCategory(): void
    {
        $this->configService->method('isDualPriceActive')->willReturn(true);
        $this->categoryHelper->method('isCategoryEntityDualPriceActive')->willReturn(false);

        $category = new CategoryEntity();
        $category->setId('cat-1');
        $category->setCustomFields([]);

        $product = new ProductEntity();
        $product->setId('prod-1');
        $product->setCategories(new CategoryCollection([$category]));

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getElements')->willReturn(['prod-1' => $product]);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->method('getResult')->willReturn($result);

        $this->subscriber->onListingResult($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertFalse($extension->get('enabled'));
    }

    public function testEnrichProductSetsEnabledFalseWithoutCategories(): void
    {
        $this->configService->method('isDualPriceActive')->willReturn(true);

        $product = new ProductEntity();
        $product->setId('prod-1');

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getElements')->willReturn(['prod-1' => $product]);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->method('getResult')->willReturn($result);

        $this->subscriber->onListingResult($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertFalse($extension->get('enabled'));
    }
}
