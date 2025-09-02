<?php declare(strict_types=1);

namespace RcDualPrice\Subscriber;

use Psr\Log\LoggerInterface;
use RcDualPrice\Service\ConfigService;
use RcDualPrice\Service\CategoryDualPriceHelper;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;

class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly CategoryDualPriceHelper $categoryHelper,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $productRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            NavigationPageLoadedEvent::class => 'onListingPageLoaded',
            SearchPageLoadedEvent::class => 'onListingPageLoaded',
            CmsPageLoadedEvent::class => 'onCmsPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        try {
            $product = $event->getPage()->getProduct();

            if (!$product instanceof ProductEntity) {
                return;
            }

            $isDualPrice = $this->isDualPriceActive($product);
            $product->addExtension('rc_dual_price_active', new ArrayStruct(['enabled' => $isDualPrice]));
            
        } catch (\Exception $e) {
            $this->logger->error('RcDualPrice: Fehler in onProductPageLoaded: ' . $e->getMessage());
        }
    }

    public function onListingPageLoaded($event): void
    {
        if (!method_exists($event, 'getPage')) {
            return;
        }

        try {
            $page = $event->getPage();
            $products = $this->getProductsFromPage($page);

            if (!$products) {
                return;
            }

            foreach ($products as $product) {
                if (!$product instanceof ProductEntity) {
                    continue;
                }

                $isDualPrice = $this->isDualPriceActive($product);
                $cssStyles = $this->configService->getCssStyles();
                $product->addExtension('rc_dual_price_active', new ArrayStruct([
                    'enabled' => $isDualPrice,
                    'cssStyles' => $cssStyles
                ]));
            }
        } catch (\Exception $e) {
            $this->logger->error('RcDualPrice: Fehler in onListingPageLoaded: ' . $e->getMessage());
        }
    }

    /**
     * Stellt sicher, dass Kategorien für ein Produkt geladen sind
     */
    private function ensureCategoriesLoaded(ProductEntity $product): void
    {
        if ($product->getCategories() !== null) {
            return;
        }

        try {
            $criteria = new Criteria([$product->getId()]);
            $criteria->addAssociation('categories');

            $result = $this->productRepository->search($criteria, Context::createDefaultContext());
            $productWithCategories = $result->first();

            if ($productWithCategories && $productWithCategories->getCategories()) {
                $product->assign(['categories' => $productWithCategories->getCategories()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('RcDualPrice: Fehler beim Nachladen der Kategorien: ' . $e->getMessage());
        }
    }

    private function getProductsFromPage($page): ?array
    {
        if (method_exists($page, 'getListing') && $page->getListing()) {
            return $page->getListing()->getElements();
        }

        if (method_exists($page, 'getSearchResult') && $page->getSearchResult()) {
            return $page->getSearchResult()->getElements();
        }

        if (method_exists($page, 'getCmsPage') && $page->getCmsPage()) {
            return $this->extractProductsFromCmsPage($page->getCmsPage());
        }

        return null;
    }

    private function extractProductsFromCmsPage($cmsPage): ?array
    {
        if (!$cmsPage || !method_exists($cmsPage, 'getSections')) {
            return null;
        }

        $products = [];
        $sections = $cmsPage->getSections();

        if (!$sections) {
            return null;
        }

        foreach ($sections as $section) {
            if (!method_exists($section, 'getBlocks')) {
                continue;
            }

            $blocks = $section->getBlocks();
            if (!$blocks) {
                continue;
            }

            foreach ($blocks as $block) {
                if (!method_exists($block, 'getSlots')) {
                    continue;
                }

                $slots = $block->getSlots();
                if (!$slots) {
                    continue;
                }

                foreach ($slots as $slot) {
                    if (!method_exists($slot, 'getData') || !$slot->getData()) {
                        continue;
                    }

                    $data = $slot->getData();

                    if (method_exists($data, 'getProducts') && $data->getProducts()) {
                        $products = array_merge($products, $data->getProducts()->getElements());
                    } elseif (method_exists($data, 'getListing') && $data->getListing()) {
                        $products = array_merge($products, $data->getListing()->getElements());
                    }
                }
            }
        }

        return empty($products) ? null : $products;
    }

    private function isDualPriceActive(ProductEntity $product): bool
    {
        if (!$this->configService->isDualPriceActive()) {
            return false;
        }

        $categories = $product->getCategories();

        if ($categories === null) {
            return false;
        }

        foreach ($categories as $category) {
            if ($this->categoryHelper->isCategoryEntityDualPriceActive($category)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CMS-Seiten verarbeiten (für Produktdetailseiten)
     */
    public function onCmsPageLoaded(CmsPageLoadedEvent $event): void
    {
        try {
            $result = $event->getResult();
            if (!$result) {
                return;
            }

            $cmsPage = $result->first();
            if (!$cmsPage) {
                return;
            }

            $this->processCmsPageProducts($cmsPage);
			
        } catch (\Exception $e) {
            $this->logger->error('RcDualPrice: Fehler in onCmsPageLoaded: ' . $e->getMessage());
        }
    }

    /**
     * Verarbeitet alle Produkte in einer CMS-Seite
     */
    private function processCmsPageProducts($cmsPage): void
    {
        if (!method_exists($cmsPage, 'getSections')) {
            return;
        }

        $sections = $cmsPage->getSections();
        if (!$sections) {
            return;
        }

        foreach ($sections as $section) {
            if (!method_exists($section, 'getBlocks')) {
                continue;
            }

            $blocks = $section->getBlocks();
            if (!$blocks) {
                continue;
            }

            foreach ($blocks as $block) {
                if (!method_exists($block, 'getSlots')) {
                    continue;
                }

                $slots = $block->getSlots();
                if (!$slots) {
                    continue;
                }

                foreach ($slots as $slot) {
                    if (!method_exists($slot, 'getData') || !$slot->getData()) {
                        continue;
                    }

                    $data = $slot->getData();

                    // Einzelprodukt in CMS-Element
                    if (method_exists($data, 'getProduct') && $data->getProduct()) {
                        $product = $data->getProduct();

                        if ($product instanceof ProductEntity) {
                            $this->ensureCategoriesLoaded($product);

                            $isDualPrice = $this->isDualPriceActive($product);
                            $cssStyles = $this->configService->getCssStyles();
                            $product->addExtension('rc_dual_price_active', new ArrayStruct([
                                'enabled' => $isDualPrice,
                                'cssStyles' => $cssStyles
                            ]));
                        }
                    }

                    // Produktliste in CMS-Element
                    if (method_exists($data, 'getProducts') && $data->getProducts()) {
                        $products = $data->getProducts()->getElements();
                        foreach ($products as $product) {
                            if ($product instanceof ProductEntity) {
                                $this->ensureCategoriesLoaded($product);
                                $isDualPrice = $this->isDualPriceActive($product);
                                $cssStyles = $this->configService->getCssStyles();
                                $product->addExtension('rc_dual_price_active', new ArrayStruct([
                                    'enabled' => $isDualPrice,
                                    'cssStyles' => $cssStyles
                                ]));
                            }
                        }
                    }
                }
            }
        }
    }
}
