<?php declare(strict_types=1);

namespace RcDualPrice\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CriteriaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onCriteria',
            ProductSearchCriteriaEvent::class => 'onCriteria',
            ProductSuggestCriteriaEvent::class => 'onCriteria',
            ProductPageCriteriaEvent::class => 'onCriteria',
        ];
    }

    public function onCriteria($event): void
    {
        $this->logger->debug('RcDualPrice: CriteriaSubscriber adding categories association');

        // Kategorien-Association hinzufügen, damit Custom Fields geladen werden
        $event->getCriteria()->addAssociation('categories');
    }
}