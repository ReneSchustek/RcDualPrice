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
// CMS Events hinzufügen
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
            // CMS Events für Produktdetailseiten
            CmsPageLoadedEvent::class => 'onCmsPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $this->logger->error('RcDualPrice: onProductPageLoaded AUFGERUFEN!');

        $product = $event->getPage()->getProduct();

        if (!$product instanceof ProductEntity) {
            $this->logger->error('RcDualPrice: Kein ProductEntity gefunden');
            return;
        }

        $isDualPrice = $this->isDualPriceActive($product);
        $this->logger->error('RcDualPrice: ProductPage isDualPriceActive = ' . ($isDualPrice ? 'true' : 'false'));

        // ✅ Extension nur hinzufügen wenn wirklich aktiv
        if ($isDualPrice) {
            $cssStyles = $this->configService->getCssStyles();
            $product->addExtension('rc_dual_price_active', new ArrayStruct([
                'enabled' => true,
                'cssStyles' => $cssStyles
            ]));
            $this->logger->error('RcDualPrice: Extension zu Product hinzugefügt');
        } else {
            $this->logger->error('RcDualPrice: Extension NICHT hinzugefügt (deaktiviert)');
        }
    }

    public function onListingPageLoaded($event): void
    {
        if (!method_exists($event, 'getPage')) {
            return;
        }

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
            
            // ✅ Extension nur hinzufügen wenn aktiv
            if ($isDualPrice) {
                $cssStyles = $this->configService->getCssStyles();
                $product->addExtension('rc_dual_price_active', new ArrayStruct([
                    'enabled' => true,
                    'cssStyles' => $cssStyles
                ]));
            }
        }
    }

    /**
     * Stellt sicher, dass Kategorien für ein Produkt geladen sind
     */
    private function ensureCategoriesLoaded(ProductEntity $product): void
    {
        // Wenn Kategorien bereits geladen sind, nichts tun
        if ($product->getCategories() !== null) {
            return;
        }

        try {
            // Kategorien für dieses Produkt nachladen
            $criteria = new Criteria([$product->getId()]);
            $criteria->addAssociation('categories');

            $result = $this->productRepository->search($criteria, Context::createDefaultContext());
            $productWithCategories = $result->first();

            if ($productWithCategories && $productWithCategories->getCategories()) {
                // Kategorien in das ursprüngliche Produkt-Objekt kopieren
                $product->assign(['categories' => $productWithCategories->getCategories()]);
                $this->logger->error('RcDualPrice: Kategorien für Produkt nachgeladen');
            }
        } catch (\Exception $e) {
            $this->logger->error('RcDualPrice: Fehler beim Nachladen der Kategorien: ' . $e->getMessage());
        }
    }

    private function getProductsFromPage($page): ?array
    {
        // Standard Listing-Seiten
        if (method_exists($page, 'getListing') && $page->getListing()) {
            return $page->getListing()->getElements();
        }

        // Such-Seiten
        if (method_exists($page, 'getSearchResult') && $page->getSearchResult()) {
            return $page->getSearchResult()->getElements();
        }

        // Navigation-Seiten mit CMS-Inhalt
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

                    // Verschiedene CMS-Element-Typen prüfen
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
        $this->logger->error('RcDualPrice: onCmsPageLoaded AUFGERUFEN!');

        $result = $event->getResult();
        if (!$result) {
            $this->logger->error('RcDualPrice: Kein Result gefunden');
            return;
        }

        // Result ist eine CmsPageCollection
        $cmsPage = $result->first();
        if (!$cmsPage) {
            $this->logger->error('RcDualPrice: Keine CMS-Seite in Collection gefunden');
            return;
        }

        $this->logger->error('RcDualPrice: CMS-Seite gefunden: ' . get_class($cmsPage));
        $this->processCmsPageProducts($cmsPage);
    }

    /**
     * Verarbeitet alle Produkte in einer CMS-Seite
     */
    private function processCmsPageProducts($cmsPage): void
    {
        $this->logger->error('RcDualPrice: processCmsPageProducts wird ausgeführt');

        if (!method_exists($cmsPage, 'getSections')) {
            $this->logger->error('RcDualPrice: CMS-Seite hat keine getSections() Methode');
            return;
        }

        $sections = $cmsPage->getSections();
        if (!$sections) {
            $this->logger->error('RcDualPrice: Keine Sections gefunden');
            return;
        }

        $this->logger->error('RcDualPrice: ' . count($sections) . ' Sections gefunden');

        foreach ($sections as $section) {
            if (!method_exists($section, 'getBlocks')) {
                continue;
            }

            $blocks = $section->getBlocks();
            if (!$blocks) {
                continue;
            }

            $this->logger->error('RcDualPrice: ' . count($blocks) . ' Blocks gefunden');

            foreach ($blocks as $block) {
                if (!method_exists($block, 'getSlots')) {
                    continue;
                }

                $slots = $block->getSlots();
                if (!$slots) {
                    continue;
                }

                $this->logger->error('RcDualPrice: ' . count($slots) . ' Slots gefunden');

                foreach ($slots as $slot) {
                    $this->logger->error('RcDualPrice: Slot-Typ: ' . $slot->getType());

                    if (!method_exists($slot, 'getData') || !$slot->getData()) {
                        $this->logger->error('RcDualPrice: Slot hat keine Daten');
                        continue;
                    }

                    $data = $slot->getData();
                    $this->logger->error('RcDualPrice: Slot-Data-Klasse: ' . get_class($data));

                    // Einzelprodukt in CMS-Element
                    if (method_exists($data, 'getProduct') && $data->getProduct()) {
                        $product = $data->getProduct();
                        $this->logger->error('RcDualPrice: Einzelprodukt gefunden: ' . $product->getId());

                        if ($product instanceof ProductEntity) {
                            // Kategorien nachladen falls nicht vorhanden
                            $this->ensureCategoriesLoaded($product);

                            $isDualPrice = $this->isDualPriceActive($product);
                            
                            // ✅ Extension nur hinzufügen wenn aktiv
                            if ($isDualPrice) {
                                $cssStyles = $this->configService->getCssStyles();
                                $product->addExtension('rc_dual_price_active', new ArrayStruct([
                                    'enabled' => true,
                                    'cssStyles' => $cssStyles
                                ]));
                                $this->logger->error('RcDualPrice: Extension zu CMS-Product hinzugefügt: aktiv');
                            } else {
                                $this->logger->error('RcDualPrice: Extension NICHT zu CMS-Product hinzugefügt: inaktiv');
                            }
                        }
                    }

                    // Produktliste in CMS-Element
                    if (method_exists($data, 'getProducts') && $data->getProducts()) {
                        $products = $data->getProducts()->getElements();
                        $this->logger->error('RcDualPrice: Produktliste gefunden mit ' . count($products) . ' Produkten');
                        foreach ($products as $product) {
                            if ($product instanceof ProductEntity) {
                                $this->ensureCategoriesLoaded($product);
                                $isDualPrice = $this->isDualPriceActive($product);
                                if ($isDualPrice) {
                                    $cssStyles = $this->configService->getCssStyles();
                                    $product->addExtension('rc_dual_price_active', new ArrayStruct([
                                        'enabled' => true,
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

    /**
     * Hilfsfunktion um Extension sicher hinzuzufügen
     */
    private function addDualPriceExtension(ProductEntity $product): void
    {
        $isDualPrice = $this->isDualPriceActive($product);
        
        if ($isDualPrice) {
            $cssStyles = $this->configService->getCssStyles();
            $product->addExtension('rc_dual_price_active', new ArrayStruct([
                'enabled' => true,
                'cssStyles' => $cssStyles
            ]));
        }
    }
}
