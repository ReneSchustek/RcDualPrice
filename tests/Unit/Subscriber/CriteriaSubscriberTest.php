<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Subscriber\CriteriaSubscriber;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

final class CriteriaSubscriberTest extends TestCase
{
    public function testGetSubscribedEventsReturnsArray(): void
    {
        $events = CriteriaSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
    }

    public function testOnCriteriaDoesNothingWhenPluginInactive(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('isDualPriceActive')->willReturn(false);

        $subscriber = new CriteriaSubscriber($configService);

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->never())->method('addAssociation');

        $event = $this->createMock(ProductListingCriteriaEvent::class);
        $event->method('getCriteria')->willReturn($criteria);

        $subscriber->onCriteria($event);
    }

    public function testOnCriteriaAddsCategoriesWhenPluginActive(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('isDualPriceActive')->willReturn(true);

        $subscriber = new CriteriaSubscriber($configService);

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())->method('addAssociation')->with('categories');

        $event = $this->createMock(ProductListingCriteriaEvent::class);
        $event->method('getCriteria')->willReturn($criteria);

        $subscriber->onCriteria($event);
    }
}
