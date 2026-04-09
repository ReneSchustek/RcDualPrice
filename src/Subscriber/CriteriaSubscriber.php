<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Subscriber;

use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CriteriaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class  => 'onCriteria',
            ProductSearchCriteriaEvent::class   => 'onCriteria',
            ProductSuggestCriteriaEvent::class  => 'onCriteria',
            ProductPageCriteriaEvent::class     => 'onCriteria',
        ];
    }

    public function onCriteria(
        ProductListingCriteriaEvent|ProductSearchCriteriaEvent|ProductSuggestCriteriaEvent|ProductPageCriteriaEvent $event,
    ): void {
        // Kategorien-Association nur laden wenn Plugin überhaupt aktiv ist
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        $event->getCriteria()->addAssociation('categories');
    }
}
