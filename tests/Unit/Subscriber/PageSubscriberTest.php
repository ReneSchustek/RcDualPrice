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
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
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

    // --- onCmsPageLoaded ---

    public function testOnCmsPageLoadedSkipsWhenPluginInactive(): void
    {
        $this->systemConfig->method('get')->willReturn(false);

        $event = $this->createMock(CmsPageLoadedEvent::class);
        $event->expects($this->never())->method('getResult');

        $this->subscriber->onCmsPageLoaded($event);
    }

    public function testOnCmsPageLoadedNoopWhenNoCmsPage(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $collection = new CmsPageCollection([]);
        $event = $this->createMock(CmsPageLoadedEvent::class);
        $event->method('getResult')->willReturn($collection);

        // Darf nicht crashen, kein Enrichment moeglich.
        $this->subscriber->onCmsPageLoaded($event);
        $this->assertCount(0, $collection);
    }

    public function testOnCmsPageLoadedEnrichesProductFromSlotWithGetProduct(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $product = $this->buildActiveSalesChannelProduct('prod-cms-1');
        $slotData = new ProductBoxStruct();
        $slotData->setProduct($product);
        $event = $this->buildCmsEventWithSlots([$slotData]);

        $this->subscriber->onCmsPageLoaded($event);

        $extension = $product->getExtension('rc_dual_price_active');
        $this->assertNotNull($extension);
        $this->assertTrue($extension->get('enabled'));
    }

    public function testOnCmsPageLoadedIgnoresSlotWithoutData(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $event = $this->buildCmsEventWithSlots([null]);

        // Darf nicht crashen.
        $this->subscriber->onCmsPageLoaded($event);
        $this->assertTrue(true);
    }

    public function testOnCmsPageLoadedHandlesSectionsAsNull(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $cmsPage = new CmsPageEntity();
        $cmsPage->setId('cms-1');
        // Sections nicht gesetzt → soll robust durchlaufen.
        $collection = new CmsPageCollection(['cms-1' => $cmsPage]);
        $event = $this->createMock(CmsPageLoadedEvent::class);
        $event->method('getResult')->willReturn($collection);

        $this->subscriber->onCmsPageLoaded($event);
        $this->assertTrue(true);
    }

    private function buildActiveProduct(string $id): ProductEntity
    {
        $category = new CategoryEntity();
        $category->setId('cat-' . $id);
        $category->setCustomFields(['rc_dual_price_active' => true]);

        $product = new ProductEntity();
        $product->setId($id);
        $product->setCategories(new CategoryCollection([$category]));

        return $product;
    }

    private function buildActiveSalesChannelProduct(string $id): SalesChannelProductEntity
    {
        $category = new CategoryEntity();
        $category->setId('cat-' . $id);
        $category->setCustomFields(['rc_dual_price_active' => true]);

        $product = new SalesChannelProductEntity();
        $product->setId($id);
        $product->setCategories(new CategoryCollection([$category]));

        return $product;
    }

    /**
     * @param list<object|null> $slotDataList
     */
    private function buildCmsEventWithSlots(array $slotDataList): CmsPageLoadedEvent
    {
        $slots = [];
        foreach ($slotDataList as $index => $data) {
            $slot = new CmsSlotEntity();
            $slot->setUniqueIdentifier('slot-' . $index);
            // Slot-Typ ist Pflicht-Property der CmsSlotEntity — ohne wuerde der Collection-Iterator crashen.
            $slot->setSlot('product-box');
            if ($data !== null) {
                $slot->setData($data);
            }
            $slots[] = $slot;
        }

        $block = new CmsBlockEntity();
        $block->setUniqueIdentifier('block-1');
        $block->setSlots(new CmsSlotCollection($slots));

        $section = new CmsSectionEntity();
        $section->setUniqueIdentifier('section-1');
        $section->setBlocks(new CmsBlockCollection([$block]));

        $cmsPage = new CmsPageEntity();
        $cmsPage->setId('cms-1');
        $cmsPage->setSections(new CmsSectionCollection([$section]));

        $collection = new CmsPageCollection(['cms-1' => $cmsPage]);

        $event = $this->createMock(CmsPageLoadedEvent::class);
        $event->method('getResult')->willReturn($collection);

        return $event;
    }
}
