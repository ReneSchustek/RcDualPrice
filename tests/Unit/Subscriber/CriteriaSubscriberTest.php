<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Subscriber\CriteriaSubscriber;
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

        // Event-Mock mit Criteria – addAssociation darf nicht aufgerufen werden
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->never())->method('addAssociation');

        $event = new class($criteria) {
            public function __construct(private readonly Criteria $criteria) {}
            public function getCriteria(): Criteria { return $this->criteria; }
        };

        $subscriber->onCriteria($event);
    }

    public function testOnCriteriaAddsCategoriesWhenPluginActive(): void
    {
        $configService = $this->createMock(ConfigService::class);
        $configService->method('isDualPriceActive')->willReturn(true);

        $subscriber = new CriteriaSubscriber($configService);

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())->method('addAssociation')->with('categories');

        $event = new class($criteria) {
            public function __construct(private readonly Criteria $criteria) {}
            public function getCriteria(): Criteria { return $this->criteria; }
        };

        $subscriber->onCriteria($event);
    }
}
