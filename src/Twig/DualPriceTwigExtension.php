<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Twig;

use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DualPriceTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rc_dual_price_active', $this->isDualPriceActive(...)),
            new TwigFunction('rc_dual_price_css_styles', $this->getCssStyles(...)),
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
}
