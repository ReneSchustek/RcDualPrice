<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Subscriber;

use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Shopware\Core\Content\Product\Events\ProductCrossSellingIdsCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductCrossSellingStreamCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Shopware\Storefront\Page\Wishlist\WishListPageProductCriteriaEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishListPageletProductCriteriaEvent;
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
            ProductListingCriteriaEvent::class            => 'onCriteria',
            ProductSearchCriteriaEvent::class             => 'onCriteria',
            ProductSuggestCriteriaEvent::class            => 'onCriteria',
            ProductPageCriteriaEvent::class               => 'onCriteria',
            // Cross-Selling ("Kunden kauften auch") laedt seine Produkte ueber eigene Criteria-Events.
            ProductCrossSellingStreamCriteriaEvent::class => 'onCriteria',
            ProductCrossSellingIdsCriteriaEvent::class    => 'onCriteria',
            // Wunschliste (angemeldet + Gast-Pagelet) laedt ihre Produkte ueber eigene Criteria-Events.
            WishListPageProductCriteriaEvent::class       => 'onCriteria',
            GuestWishListPageletProductCriteriaEvent::class => 'onCriteria',
        ];
    }

    public function onCriteria(
        ProductListingCriteriaEvent|ProductSearchCriteriaEvent|ProductSuggestCriteriaEvent|ProductPageCriteriaEvent|ProductCrossSellingStreamCriteriaEvent|ProductCrossSellingIdsCriteriaEvent|WishListPageProductCriteriaEvent|GuestWishListPageletProductCriteriaEvent $event,
    ): void {
        // Kategorien-Association nur laden wenn Plugin überhaupt aktiv ist
        if (!$this->configService->isDualPriceActive()) {
            return;
        }

        // Das Autocomplete-Dropdown (Suggest) rendert den Zweitpreis nicht — die zusaetzliche
        // categories-Association waere dort reine Last pro Tastendruck.
        if ($event instanceof ProductSuggestCriteriaEvent) {
            return;
        }

        $event->getCriteria()->addAssociation('categories');
    }
}
