<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Subscriber;

use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Checkout\Customer\Event\CustomerWishlistProductListingResultEvent;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Product\Events\ProductCrossSellingsLoadedEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoadedEvent;
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
            ProductPageLoadedEvent::class          => 'onProductPageLoaded',
            ProductListingResultEvent::class       => 'onListingResult',
            CmsPageLoadedEvent::class                          => 'onCmsPageLoaded',
            ProductCrossSellingsLoadedEvent::class             => 'onCrossSellingsLoaded',
            CustomerWishlistProductListingResultEvent::class   => 'onCustomerWishlistResult',
            GuestWishlistPageletLoadedEvent::class             => 'onGuestWishlistLoaded',
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

        foreach ($event->getResult()->getEntities() as $product) {
            $this->enrichProduct($product);
        }
    }

    public function onCrossSellingsLoaded(ProductCrossSellingsLoadedEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        // Cross-Selling wird oft per AJAX-Route geladen (kein CmsPageLoadedEvent). Hier werden die
        // Produkte aller Cross-Selling-Gruppen mit der Extension angereichert.
        foreach ($event->getCrossSellings() as $crossSelling) {
            foreach ($crossSelling->getProducts() as $product) {
                $this->enrichProduct($product);
            }
        }
    }

    public function onCustomerWishlistResult(CustomerWishlistProductListingResultEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        foreach ($event->getResult()->getEntities() as $product) {
            $this->enrichProduct($product);
        }
    }

    public function onGuestWishlistLoaded(GuestWishlistPageletLoadedEvent $event): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        foreach ($event->getPagelet()->getSearchResult()->getProducts() as $product) {
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

        // CMS-Aufbau durchlaufen: Section → Block → Slot. Was an einem Slot hängt, entscheidet
        // `enrichSlot()`.
        foreach ($cmsPage->getSections() ?? [] as $section) {
            foreach ($section->getBlocks() ?? [] as $block) {
                foreach ($block->getSlots() ?? [] as $slot) {
                    $this->enrichSlot($slot);
                }
            }
        }
    }

    /**
     * Greift die drei Quellen ab, unter denen ein Slot Produkte führen kann: `getProduct()`
     * (Einzel-Produkt-Box), `getProducts()` (Cross-Selling/Cluster) und `getListing()`
     * (Listing-Element). Andere Strukturen werden übergangen.
     *
     * Eigene Methode seit dem 2026-08-19: Zusammen mit der Traversierung lag die Verzweigungszahl
     * von `onCmsPageLoaded()` bei 20, die Regel zieht die Grenze bei 15.
     */
    private function enrichSlot(CmsSlotEntity $slot): void
    {
        $data = $slot->getData();
        if (!$data) {
            return;
        }

        if (method_exists($data, 'getProduct') && $data->getProduct() instanceof ProductEntity) {
            $this->enrichProduct($data->getProduct());
        }

        if (method_exists($data, 'getProducts')) {
            $this->enrichAll($data->getProducts()?->getElements() ?? []);
        }

        if (method_exists($data, 'getListing')) {
            $this->enrichAll($data->getListing()?->getElements() ?? []);
        }
    }

    /**
     * Reichert alles an, was ein Produkt ist. Der Typvergleich bleibt: In einer Slot-Sammlung
     * steht nicht zwingend nur Ware.
     *
     * @param array<int|string, mixed> $entities
     */
    private function enrichAll(array $entities): void
    {
        foreach ($entities as $entity) {
            if ($entity instanceof ProductEntity) {
                $this->enrichProduct($entity);
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
