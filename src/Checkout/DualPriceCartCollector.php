<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Checkout;

use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Schreibt pro Produkt-Line-Item das Per-Kategorie-Flag `rcDualPriceActive` in den Payload.
 *
 * Anders als die Runtime-Extension auf dem Produkt (PageSubscriber) reist der Payload in den
 * Warenkorb, den Checkout und die Bestellung. Erst dadurch kann das Line-Item-Template den
 * Zweitpreis auf genau die Produkte begrenzen, deren Kategorie den Zweitpreis aktiviert hat —
 * statt ihn bei global aktivem Plugin fuer alle Positionen zu zeigen.
 */
final class DualPriceCartCollector implements CartDataCollectorInterface
{
    public const PAYLOAD_KEY = 'rcDualPriceActive';

    private const DATA_KEY_PREFIX = 'rc-dual-price-active-';

    /**
     * @param SalesChannelRepository<covariant \Shopware\Core\Content\Product\ProductCollection> $productRepository
     */
    public function __construct(
        private readonly ConfigService $configService,
        private readonly CategoryDualPriceHelper $categoryHelper,
        private readonly SalesChannelRepository $productRepository,
    ) {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        $lineItems = $original->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        if ($lineItems === []) {
            return;
        }

        $this->resolveMissingFlags($data, $lineItems, $context);

        foreach ($lineItems as $lineItem) {
            $referencedId = $lineItem->getReferencedId();
            if ($referencedId === null) {
                continue;
            }

            $lineItem->setPayloadValue(self::PAYLOAD_KEY, (bool) $data->get(self::DATA_KEY_PREFIX . $referencedId));
        }
    }

    /**
     * Laedt die Kategorie-Flags nur fuer noch nicht aufgeloeste Produkte — memoized ueber die
     * CartDataCollection, damit wiederholte Cart-Berechnungen keine erneute Query ausloesen.
     *
     * @param LineItem[] $lineItems
     */
    private function resolveMissingFlags(CartDataCollection $data, array $lineItems, SalesChannelContext $context): void
    {
        $missingIds = [];
        foreach ($lineItems as $lineItem) {
            $referencedId = $lineItem->getReferencedId();
            if ($referencedId !== null && !$data->has(self::DATA_KEY_PREFIX . $referencedId)) {
                $missingIds[$referencedId] = $referencedId;
            }
        }

        if ($missingIds === []) {
            return;
        }

        $criteria = new Criteria(array_values($missingIds));
        $criteria->addAssociation('categories');
        $products = $this->productRepository->search($criteria, $context)->getEntities();

        foreach ($missingIds as $productId) {
            $product = $products->get($productId);
            $data->set(
                self::DATA_KEY_PREFIX . $productId,
                $product instanceof ProductEntity && $this->isProductDualPriceActive($product),
            );
        }
    }

    private function isProductDualPriceActive(ProductEntity $product): bool
    {
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
}
