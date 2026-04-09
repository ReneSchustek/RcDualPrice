<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Twig\DualPriceTwigExtension;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twig\TwigFunction;

final class DualPriceTwigExtensionTest extends TestCase
{
    private function createConfigService(mixed $configValue): ConfigService
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn($configValue);

        return new ConfigService($systemConfig);
    }

    public function testRegistersTwoFunctions(): void
    {
        $extension = new DualPriceTwigExtension($this->createConfigService(null));

        $functions = $extension->getFunctions();

        $this->assertCount(2, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $names = array_map(fn (TwigFunction $f) => $f->getName(), $functions);
        $this->assertContains('rc_dual_price_active', $names);
        $this->assertContains('rc_dual_price_css_styles', $names);
    }

    public function testIsDualPriceActiveDelegatesToConfigService(): void
    {
        // Standardwert (null) liefert true via ConfigService::isDualPriceActive()
        $extension = new DualPriceTwigExtension($this->createConfigService(null));

        $this->assertTrue($extension->isDualPriceActive());
    }

    public function testIsDualPriceActiveReturnsFalseWhenInactive(): void
    {
        $extension = new DualPriceTwigExtension($this->createConfigService(false));

        $this->assertFalse($extension->isDualPriceActive());
    }

    public function testGetCssStylesContainsAllProperties(): void
    {
        $extension = new DualPriceTwigExtension($this->createConfigService(null));

        $css = $extension->getCssStyles();
        $this->assertStringContainsString('color:', $css);
        $this->assertStringContainsString('font-size:', $css);
        $this->assertStringContainsString('font-weight:', $css);
        $this->assertStringContainsString('margin-top:', $css);
    }
}
