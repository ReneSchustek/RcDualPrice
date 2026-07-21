<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Cms;

use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\ProductDefinition;

/**
 * Dekoriert den Core-Resolver des CMS-Produkt-Sliders.
 *
 * Der Slider laedt seine Produkte ueber prozessor-interne Criteria und dispatcht — anders als
 * Listing/Suche/Cross-Selling — KEIN Criteria-Event. Ohne die `categories`-Association fehlt den
 * Slider-Produkten die Grundlage, auf der der PageSubscriber den Zweitpreis-Zweig entscheidet.
 * Der Decorator ergaenzt die Association nachtraeglich auf den Produkt-Criteria.
 */
final class ProductSliderCategoriesResolverDecorator implements CmsElementResolverInterface
{
    public function __construct(
        private readonly CmsElementResolverInterface $decorated,
        private readonly ConfigService $configService,
    ) {
    }

    public function getType(): string
    {
        return $this->decorated->getType();
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $collection = $this->decorated->collect($slot, $resolverContext);

        if ($collection === null || !$this->configService->isDualPriceActive()) {
            return $collection;
        }

        foreach ($collection->all() as $definition => $criterias) {
            if ($definition !== ProductDefinition::class) {
                continue;
            }

            foreach ($criterias as $criteria) {
                $criteria->addAssociation('categories');
            }
        }

        return $collection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $this->decorated->enrich($slot, $resolverContext, $result);
    }
}
