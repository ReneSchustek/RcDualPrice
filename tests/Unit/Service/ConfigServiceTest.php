<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class ConfigServiceTest extends TestCase
{
    private ConfigService $configService;
    private SystemConfigService $systemConfig;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfigService::class);
        $this->configService = new ConfigService($this->systemConfig);
    }

    public function testIsDualPriceActiveReturnsTrueByDefault(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $this->assertTrue($this->configService->isDualPriceActive());
    }

    public function testIsDualPriceActiveReturnsFalseWhenDisabled(): void
    {
        $this->systemConfig->method('get')->willReturn(false);

        $this->assertFalse($this->configService->isDualPriceActive());
    }

    public function testGetTextColorReturnsDefault(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $this->assertSame('#6c757d', $this->configService->getTextColor());
    }

    public function testGetTextColorReturnsConfiguredValue(): void
    {
        $this->systemConfig->method('get')->willReturn('#ff0000');

        $this->assertSame('#ff0000', $this->configService->getTextColor());
    }

    public function testGetMarginTopReturnsDefaultFour(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $this->assertSame(4, $this->configService->getMarginTop());
    }

    public function testGetCssStylesContainsAllProperties(): void
    {
        $this->systemConfig->method('get')->willReturn(null);
        $css = $this->configService->getCssStyles();

        $this->assertStringContainsString('color:', $css);
        $this->assertStringContainsString('font-size:', $css);
        $this->assertStringContainsString('font-weight:', $css);
        $this->assertStringContainsString('margin-top:', $css);
    }

    public function testGetCssStylesWithLargeFontSize(): void
    {
        $this->systemConfig->method('get')->willReturnCallback(
            fn(string $key) => match(true) {
                str_ends_with($key, 'fontSize') => 'large',
                default => null,
            }
        );

        $this->assertStringContainsString('1.125rem', $this->configService->getCssStyles());
    }

    public function testGetTextColorRejectsMaliciousInput(): void
    {
        $mock = $this->createMock(SystemConfigService::class);
        $mock->method('get')->willReturnCallback(
            fn(string $key) => str_ends_with($key, 'textColor') ? 'red; display:none' : null,
        );

        $service = new ConfigService($mock);

        // Ungültige Eingabe fällt auf den Standardwert zurück
        $this->assertSame('#6c757d', $service->getTextColor());
    }

    public function testGetTextColorAcceptsRgbValue(): void
    {
        $mock = $this->createMock(SystemConfigService::class);
        $mock->method('get')->willReturnCallback(
            fn(string $key) => str_ends_with($key, 'textColor') ? 'rgb(255, 0, 0)' : null,
        );

        $service = new ConfigService($mock);

        $this->assertSame('rgb(255, 0, 0)', $service->getTextColor());
    }

    public function testGetFontWeightReturnsDefaultNormal(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $this->assertSame('normal', $this->configService->getFontWeight());
    }

    public function testGetFontWeightAcceptsBold(): void
    {
        $this->configFor('fontWeight', 'bold');

        $this->assertSame('bold', $this->configService->getFontWeight());
    }

    public function testGetFontWeightRejectsMaliciousInput(): void
    {
        // fontWeight fliesst ins style-Attribut — nur 'normal'/'bold' erlaubt, sonst Fallback.
        $this->configFor('fontWeight', 'bold; display:none');

        $this->assertSame('normal', $this->configService->getFontWeight());
    }

    public function testGetFontSizeReturnsDefaultSmall(): void
    {
        $this->systemConfig->method('get')->willReturn(null);

        $this->assertSame('small', $this->configService->getFontSize());
    }

    public function testGetFontSizeReturnsConfiguredValue(): void
    {
        $this->configFor('fontSize', 'large');

        $this->assertSame('large', $this->configService->getFontSize());
    }

    public function testGetMarginTopClampsAboveHundred(): void
    {
        // Begrenzung 0-100px, da der Wert ins style-Attribut fliesst.
        $this->configFor('marginTop', 9999);

        $this->assertSame(100, $this->configService->getMarginTop());
    }

    public function testGetMarginTopClampsBelowZero(): void
    {
        $this->configFor('marginTop', -50);

        $this->assertSame(0, $this->configService->getMarginTop());
    }

    public function testGetCssStylesReflectsFontWeightAndMarginTop(): void
    {
        $this->systemConfig->method('get')->willReturnCallback(
            fn(string $key) => match(true) {
                str_ends_with($key, 'fontWeight') => 'bold',
                str_ends_with($key, 'marginTop') => 12,
                default => null,
            }
        );

        $css = $this->configService->getCssStyles();

        $this->assertStringContainsString('font-weight: bold;', $css);
        $this->assertStringContainsString('margin-top: 12px;', $css);
    }

    private function configFor(string $suffix, mixed $value): void
    {
        $this->systemConfig->method('get')->willReturnCallback(
            fn(string $key) => str_ends_with($key, $suffix) ? $value : null,
        );
    }
}
