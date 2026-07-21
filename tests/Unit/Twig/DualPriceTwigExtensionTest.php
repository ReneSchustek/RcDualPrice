<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Service\DualPriceCalculator;
use Ruhrcoder\RcDualPrice\Twig\DualPriceTwigExtension;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twig\TwigFunction;

final class DualPriceTwigExtensionTest extends TestCase
{
    private function createExtension(mixed $configValue): DualPriceTwigExtension
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn($configValue);

        return new DualPriceTwigExtension(new ConfigService($systemConfig), new DualPriceCalculator());
    }

    public function testRegistersThreeFunctions(): void
    {
        $functions = $this->createExtension(null)->getFunctions();

        $this->assertCount(3, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $names = array_map(fn (TwigFunction $f) => $f->getName(), $functions);
        $this->assertContains('rc_dual_price_active', $names);
        $this->assertContains('rc_dual_price_css_styles', $names);
        $this->assertContains('rc_dual_price_convert', $names);
    }

    public function testIsDualPriceActiveDelegatesToConfigService(): void
    {
        // Standardwert (null) liefert true via ConfigService::isDualPriceActive()
        $this->assertTrue($this->createExtension(null)->isDualPriceActive());
    }

    public function testIsDualPriceActiveReturnsFalseWhenInactive(): void
    {
        $this->assertFalse($this->createExtension(false)->isDualPriceActive());
    }

    public function testConvertDelegatesToCalculator(): void
    {
        $extension = $this->createExtension(null);

        $this->assertSame(100.0, $extension->convert(119.0, 19.0, 'gross'));
        $this->assertNull($extension->convert(119.0, 19.0, 'tax-free'));
    }

    public function testGetCssStylesContainsAllProperties(): void
    {
        $css = $this->createExtension(null)->getCssStyles();
        $this->assertStringContainsString('color:', $css);
        $this->assertStringContainsString('font-size:', $css);
        $this->assertStringContainsString('font-weight:', $css);
        $this->assertStringContainsString('margin-top:', $css);
    }
}
