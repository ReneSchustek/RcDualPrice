<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Subscriber;

use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly CategoryDualPriceHelper $categoryHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class    => 'onProductPageLoaded',
            ProductListingResultEvent::class => 'onListingResult',
            CmsPageLoadedEvent::class        => 'onCmsPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        $this->enrichProduct($event->getPage()->getProduct());
    }

    public function onListingResult(ProductListingResultEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        foreach ($event->getResult()->getElements() as $product) {
            $this->enrichProduct($product);
        }
    }

    public function onCmsPageLoaded(CmsPageLoadedEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        $cmsPage = $event->getResult()->first();
        if ($cmsPage === null) {
            return;
        }

        foreach ($cmsPage->getSections() ?? [] as $section) {
            foreach ($section->getBlocks() ?? [] as $block) {
                foreach ($block->getSlots() ?? [] as $slot) {
                    $data = $slot->getData();
                    if (!$data) {
                        continue;
                    }

                    if (method_exists($data, 'getProduct') && $data->getProduct() instanceof ProductEntity) {
                        $this->enrichProduct($data->getProduct());
                    }

                    if (method_exists($data, 'getProducts')) {
                        foreach ($data->getProducts()?->getElements() ?? [] as $product) {
                            if ($product instanceof ProductEntity) {
                                $this->enrichProduct($product);
                            }
                        }
                    }

                    if (method_exists($data, 'getListing')) {
                        foreach ($data->getListing()?->getElements() ?? [] as $product) {
                            if ($product instanceof ProductEntity) {
                                $this->enrichProduct($product);
                            }
                        }
                    }
                }
            }
        }
    }

    private function enrichProduct(ProductEntity $product): void
    {
        $categories = $product->getCategories();
        $enabled = false;

        if ($categories !== null) {
            foreach ($categories as $category) {
                if ($this->categoryHelper->isCategoryEntityDualPriceActive($category)) {
                    $enabled = true;
                    break;
                }
            }
        }

        $product->addExtension('rc_dual_price_active', new ArrayStruct([
            'enabled'   => $enabled,
            'cssStyles' => $enabled ? $this->configService->getCssStyles() : '',
        ]));
    }
}
