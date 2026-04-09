<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\Service\ConfigService;
use Ruhrcoder\RcDualPrice\Subscriber\CriteriaSubscriber;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class CriteriaSubscriberTest extends TestCase
{
    private function createConfigService(mixed $activeValue): ConfigService
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn($activeValue);

        return new ConfigService($systemConfig);
    }

    public function testGetSubscribedEventsReturnsArray(): void
    {
        $events = CriteriaSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
    }

    public function testOnCriteriaDoesNothingWhenPluginInactive(): void
    {
        $subscriber = new CriteriaSubscriber($this->createConfigService(false));

        $criteria = new Criteria();

        $event = $this->createMock(ProductListingCriteriaEvent::class);
        $event->expects($this->never())->method('getCriteria');

        $subscriber->onCriteria($event);

        $this->assertSame([], $criteria->getAssociations());
    }

    public function testOnCriteriaAddsCategoriesWhenPluginActive(): void
    {
        $subscriber = new CriteriaSubscriber($this->createConfigService(null));

        $criteria = new Criteria();

        $event = $this->createMock(ProductListingCriteriaEvent::class);
        $event->method('getCriteria')->willReturn($criteria);

        $subscriber->onCriteria($event);

        $this->assertArrayHasKey('categories', $criteria->getAssociations());
    }
}
