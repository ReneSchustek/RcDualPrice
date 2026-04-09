<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

final class ConfigService
{
    private const PLUGIN_CONFIG_KEY = 'RcDualPrice.config';

    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function isDualPriceActive(): bool
    {
        return (bool) $this->get('.active', true);
    }

    public function getTextColor(): string
    {
        $color = (string) $this->get('.textColor', '#6c757d');

        // Nur bekannte CSS-Farbformate zulassen — Admin-Eingabe landet ungefiltert im style-Attribut
        if (!preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]+|rgb\(\d+,\s*\d+,\s*\d+\)|rgba\(\d+,\s*\d+,\s*\d+,\s*[\d.]+\))$/', $color)) {
            return '#6c757d';
        }

        return $color;
    }

    public function getFontSize(): string
    {
        return (string) $this->get('.fontSize', 'small');
    }

    public function getFontWeight(): string
    {
        $weight = (string) $this->get('.fontWeight', 'normal');

        // XSS-Schutz: Nur erlaubte Werte, da fontWeight ins style-Attribut fliesst
        return \in_array($weight, ['normal', 'bold'], true) ? $weight : 'normal';
    }

    public function getMarginTop(): int
    {
        // Begrenzt auf 0-100px, da der Wert ins style-Attribut fliesst
        return min(max((int) $this->get('.marginTop', 4), 0), 100);
    }

    public function getCssStyles(): string
    {
        $fontSize = match($this->getFontSize()) {
            'small'  => '0.875rem',
            'normal' => '1rem',
            'large'  => '1.125rem',
            default  => '0.875rem',
        };

        return sprintf(
            'color: %s; font-size: %s; font-weight: %s; margin-top: %dpx;',
            $this->getTextColor(),
            $fontSize,
            $this->getFontWeight(),
            $this->getMarginTop()
        );
    }

    private function get(string $keySuffix, mixed $default): mixed
    {
        $key = self::PLUGIN_CONFIG_KEY . $keySuffix;

        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->systemConfigService->get($key) ?? $default;
        }

        return $this->cache[$key];
    }
}
