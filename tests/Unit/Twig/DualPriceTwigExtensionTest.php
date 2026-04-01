<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Twig\DualPriceTwigExtension;
use Twig\TwigFunction;

final class DualPriceTwigExtensionTest extends TestCase
{
    public function testRegistersTwoFunctions(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $extension = new DualPriceTwigExtension($configService);

        $functions = $extension->getFunctions();

        $this->assertCount(2, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $names = array_map(fn(TwigFunction $f) => $f->getName(), $functions);
        $this->assertContains('rc_dual_price_active', $names);
        $this->assertContains('rc_dual_price_css_styles', $names);
    }

    public function testIsDualPriceActiveDelegatesToConfigService(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('isDualPriceActive')->willReturn(true);

        $extension = new DualPriceTwigExtension($configService);

        $this->assertTrue($extension->isDualPriceActive());
    }

    public function testIsDualPriceActiveReturnsFalseWhenInactive(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('isDualPriceActive')->willReturn(false);

        $extension = new DualPriceTwigExtension($configService);

        $this->assertFalse($extension->isDualPriceActive());
    }

    public function testGetCssStylesDelegatesToConfigService(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('getCssStyles')->willReturn('color: #ff0000; font-size: 1rem;');

        $extension = new DualPriceTwigExtension($configService);

        $this->assertSame('color: #ff0000; font-size: 1rem;', $extension->getCssStyles());
    }
}
