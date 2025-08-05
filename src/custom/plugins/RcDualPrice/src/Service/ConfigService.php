<?php declare(strict_types=1);

namespace RcDualPrice\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    private const PLUGIN_CONFIG_KEY = 'RcDualPrice.config';

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function isDualPriceActive(): bool
    {
        return (bool) $this->systemConfigService->get(self::PLUGIN_CONFIG_KEY . '.active', null, true);
    }

    public function getTextColor(): string
    {
        return (string) $this->systemConfigService->get(self::PLUGIN_CONFIG_KEY . '.textColor', null, '#6c757d');
    }

    public function getFontSize(): string
    {
        return (string) $this->systemConfigService->get(self::PLUGIN_CONFIG_KEY . '.fontSize', null, 'small');
    }

    public function getFontWeight(): string
    {
        return (string) $this->systemConfigService->get(self::PLUGIN_CONFIG_KEY . '.fontWeight', null, 'normal');
    }

    public function getMarginTop(): int
    {
        return (int) $this->systemConfigService->get(self::PLUGIN_CONFIG_KEY . '.marginTop', null, 4);
    }

    /**
     * Gibt alle Styling-Eigenschaften als CSS-String zurück
     */
    public function getCssStyles(): string
    {
        $fontSize = match($this->getFontSize()) {
            'small' => '0.875rem',
            'normal' => '1rem',
            'large' => '1.125rem',
            default => '0.875rem'
        };

        return sprintf(
            'color: %s; font-size: %s; font-weight: %s; margin-top: %dpx;',
            $this->getTextColor(),
            $fontSize,
            $this->getFontWeight(),
            $this->getMarginTop()
        );
    }
}