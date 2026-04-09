<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Subscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Subscriber\PageSubscriber;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class PageSubscriberTest extends TestCase
{
    private SystemConfigService&MockObject $systemConfig;
    private PageSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfigService::class);
        $configService = new ConfigService($this->systemConfig);
        $categoryHelper = new CategoryDualPriceHelper();
        $this->subscriber = new PageSubscriber($configService, $categoryHelper);
    }

    public function testGetSubscribedEventsReturnsArray(): void
    {
        $events = PageSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertCount(3, $events);
    }

    public function testOnListingResultSkipsWhenPluginInactive(): void
    {
        $this->systemConfig->method('get')->willReturn(false);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->expects($this->never())->method('getResult');

        $this->subscriber->onListingResult($event);
    }

    public function testOnListingResultEnrichesProductsWhenActive(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $category = new CategoryEntity();
        $category->setId('cat-1');
        $category->setCustomFields(['rc_dual_price_active' => true]);

        $product = new ProductEntity();
        $product->setId('prod-1');
        $product->setCategories(new CategoryCollection([$category]));

        $result = $this->createMock(ProductListingResult::class);
        $result->method('getElements')->willReturn(['prod-1' => $product]);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->method('getResult')->willReturn($result);

        $this->subscriber->onListingResult($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertTrue($extension->get('enabled'));
        $this->assertStringContainsString('color:', (string) $extension->get('cssStyles'));
    }

    public function testEnrichProductSetsEnabledFalseWithoutMatchingCategory(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $category = new CategoryEntity();
        $category->setId('cat-1');
        $category->setCustomFields(['rc_dual_price_active' => false]);

        $product = new ProductEntity();
        $product->setId('prod-1');
        $product->setCategories(new CategoryCollection([$category]));

        $result = $this->createMock(ProductListingResult::class);
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
        $this->systemConfig->method('get')->willReturn(null);

        $product = new ProductEntity();
        $product->setId('prod-1');

        $result = $this->createMock(ProductListingResult::class);
        $result->method('getElements')->willReturn(['prod-1' => $product]);

        $event = $this->createMock(ProductListingResultEvent::class);
        $event->method('getResult')->willReturn($result);

        $this->subscriber->onListingResult($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertFalse($extension->get('enabled'));
    }
}
