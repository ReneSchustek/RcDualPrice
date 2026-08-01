<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Checkout;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Checkout\DualPriceCartCollector;
use Ruhrcoder\RcDualPrice\Service\CategoryDualPriceHelper;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class DualPriceCartCollectorTest extends TestCase
{
    private SystemConfigService&MockObject $systemConfig;

    /** @var SalesChannelRepository<ProductCollection>&MockObject */
    private SalesChannelRepository&MockObject $productRepository;

    private DualPriceCartCollector $collector;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfigService::class);
        $this->productRepository = $this->createMock(SalesChannelRepository::class);

        $this->collector = new DualPriceCartCollector(
            new ConfigService($this->systemConfig),
            new CategoryDualPriceHelper(),
            $this->productRepository,
        );
    }

    public function testWritesTruePayloadForProductInActiveCategory(): void
    {
        $this->givenPluginActive(true);
        $this->givenProducts([
            'active-product'   => true,
            'inactive-product' => false,
        ]);

        $cart = $this->cartWithProducts(['active-product', 'inactive-product']);

        $this->collector->collect(new CartDataCollection(), $cart, $this->context(), new CartBehavior());

        self::assertTrue($this->lineItem($cart, 'active-product')->getPayloadValue(DualPriceCartCollector::PAYLOAD_KEY));
        self::assertFalse($this->lineItem($cart, 'inactive-product')->getPayloadValue(DualPriceCartCollector::PAYLOAD_KEY));
        self::assertArrayHasKey(
            DualPriceCartCollector::PAYLOAD_KEY,
            $this->lineItem($cart, 'active-product')->getPayload(),
        );
    }

    public function testDoesNothingWhenPluginInactive(): void
    {
        $this->givenPluginActive(false);
        $this->productRepository->expects(self::never())->method('search');

        $cart = $this->cartWithProducts(['some-product']);

        $this->collector->collect(new CartDataCollection(), $cart, $this->context(), new CartBehavior());

        self::assertArrayNotHasKey(
            DualPriceCartCollector::PAYLOAD_KEY,
            $this->lineItem($cart, 'some-product')->getPayload(),
        );
    }

    public function testResolvesEachProductOnlyOnceViaCartDataCollection(): void
    {
        $this->givenPluginActive(true);
        $this->givenProducts(['active-product' => true]);
        // Zwei collect-Läufe (wie bei wiederholter Cart-Berechnung) dürfen nur EINE Query auslösen.
        $this->productRepository->expects(self::once())->method('search');

        $cart = $this->cartWithProducts(['active-product']);
        $data = new CartDataCollection();

        $this->collector->collect($data, $cart, $this->context(), new CartBehavior());
        $this->collector->collect($data, $cart, $this->context(), new CartBehavior());

        self::assertTrue($this->lineItem($cart, 'active-product')->getPayloadValue(DualPriceCartCollector::PAYLOAD_KEY));
    }

    private function givenPluginActive(bool $active): void
    {
        $this->systemConfig->method('get')->willReturnCallback(
            static fn (string $key): mixed => $key === 'RcDualPrice.config.active' ? $active : null,
        );
    }

    /**
     * @param array<string, bool> $productsWithFlag Produkt-ID => Kategorie-Flag aktiv?
     */
    private function givenProducts(array $productsWithFlag): void
    {
        $products = new ProductCollection();
        foreach ($productsWithFlag as $productId => $flag) {
            $category = new CategoryEntity();
            $category->setId(md5($productId . '-cat'));
            $category->setCustomFields(['rc_dual_price_active' => $flag]);

            $product = new ProductEntity();
            $product->setId($productId);
            $product->setCategories(new CategoryCollection([$category]));

            $products->add($product);
        }

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getEntities')->willReturn($products);
        $this->productRepository->method('search')->willReturn($result);
    }

    /**
     * @param string[] $productIds
     */
    private function cartWithProducts(array $productIds): Cart
    {
        $cart = new Cart('test-token');
        foreach ($productIds as $productId) {
            $cart->add(new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1));
        }

        return $cart;
    }

    private function context(): SalesChannelContext
    {
        return $this->createMock(SalesChannelContext::class);
    }

    /**
     * Holt eine Position aus dem Warenkorb und stellt sicher, dass es sie gibt.
     * Cart::get() liefert null, wenn die Kennung nicht vorkommt — ohne diese Prüfung
     * würde der Test an einem Aufruf auf null abbrechen statt am eigentlichen Vergleich.
     */
    private function lineItem(Cart $cart, string $id): LineItem
    {
        $lineItem = $cart->get($id);
        self::assertInstanceOf(LineItem::class, $lineItem);

        return $lineItem;
    }

}
