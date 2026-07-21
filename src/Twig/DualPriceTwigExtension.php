<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Twig;

use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Service\DualPriceCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DualPriceTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly DualPriceCalculator $calculator,
    ) {
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rc_dual_price_active', $this->isDualPriceActive(...)),
            new TwigFunction('rc_dual_price_css_styles', $this->getCssStyles(...)),
            new TwigFunction('rc_dual_price_convert', $this->convert(...)),
        ];
    }

    public function isDualPriceActive(): bool
    {
        return $this->configService->isDualPriceActive();
    }

    public function getCssStyles(): string
    {
        return $this->configService->getCssStyles();
    }

    /**
     * Rechnet den Einzelpreis in den jeweils anderen Steuer-State um (Zweitpreis).
     * null = nicht darstellbar (tax-free/unbekannter State/Satz 0) → Template rendert nichts.
     */
    public function convert(?float $unitPrice, ?float $taxRate, ?string $taxState): ?float
    {
        return $this->calculator->convert($unitPrice, $taxRate, $taxState);
    }
}
