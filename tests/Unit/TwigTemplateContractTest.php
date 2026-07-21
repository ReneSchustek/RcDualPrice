<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Nagelt die Ziel-Templates und Blocknamen der drei Storefront-Overrides fest.
 * Ein `sw_extends`-Block, den es im Ziel-Template nicht (mehr) gibt, wird von Twig
 * stillschweigend ignoriert -- die Zweitpreis-Ausgabe rendert dann nie, ohne dass
 * ein Test anschlaegt. Dieser Test schlaegt an, sobald Shopware einen der Blocknamen
 * umbenennt oder ein Override auf einen Phantom-Block gerichtet wird.
 */
final class TwigTemplateContractTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function overrideProvider(): array
    {
        return [
            'buy-widget-price' => [
                'storefront/component/buy-widget/buy-widget-price.html.twig',
                '@Storefront/storefront/component/buy-widget/buy-widget-price.html.twig',
                [
                    'buy_widget_price_content',
                    'buy_widget_price_block_table_head_inner',
                    'buy_widget_price_block_table_body_cell_price',
                ],
            ],
            'line-item-product' => [
                'storefront/component/line-item/type/product.html.twig',
                '@Storefront/storefront/component/line-item/type/product.html.twig',
                [
                    'component_line_item_type_product_col_unit_price',
                    'component_line_item_type_product_col_total_price',
                ],
            ],
            'product-card-price-unit' => [
                'storefront/component/product/card/price-unit.html.twig',
                '@Storefront/storefront/component/product/card/price-unit.html.twig',
                [
                    'component_product_box_price',
                ],
            ],
        ];
    }

    /**
     * @param list<string> $requiredBlocks
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('overrideProvider')]
    public function testOverrideZieltAufGueltigeCoreBloecke(string $relativePath, string $extendsTarget, array $requiredBlocks): void
    {
        $path = __DIR__ . '/../../src/Resources/views/' . $relativePath;
        $content = file_get_contents($path);
        self::assertIsString($content, 'Template nicht lesbar: ' . $path);

        self::assertStringContainsString(
            "{% sw_extends '" . $extendsTarget . "' %}",
            $content,
            'sw_extends-Ziel fehlt in ' . $relativePath,
        );

        foreach ($requiredBlocks as $block) {
            self::assertStringContainsString(
                '{% block ' . $block . ' %}',
                $content,
                'Block ' . $block . ' fehlt in ' . $relativePath,
            );
        }
    }
}
