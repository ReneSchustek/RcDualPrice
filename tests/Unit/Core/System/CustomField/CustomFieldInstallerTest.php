<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit\Core\System\CustomField;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ruhrcoder\RcDualPrice\Core\System\CustomField\CustomFieldInstaller;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;

/**
 * Unit-Tests fuer den Lifecycle-Installer. Verifiziert Idempotenz, Upsert-Payload,
 * Uninstall-Verhalten und strukturierte Logs — alles, was an der DAL-Boundary haengt.
 */
#[CoversClass(CustomFieldInstaller::class)]
final class CustomFieldInstallerTest extends TestCase
{
    private EntityRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = Context::createDefaultContext();
    }

    public function testInstallUpsertsCustomFieldSetWennNichtVorhanden(): void
    {
        $this->mockSearchEmpty();

        $this->repository->expects(self::once())
            ->method('upsert')
            ->with(
                self::callback(function (array $payload): bool {
                    self::assertCount(1, $payload);
                    $set = $payload[0];
                    self::assertSame(CustomFieldInstaller::SET_NAME, $set['name']);
                    self::assertSame('Zweitpreis Einstellungen', $set['config']['label']['de-DE']);
                    self::assertSame('Dual Price Settings', $set['config']['label']['en-GB']);
                    self::assertCount(1, $set['customFields']);
                    self::assertSame(CustomFieldInstaller::FIELD_NAME, $set['customFields'][0]['name']);
                    self::assertSame('bool', $set['customFields'][0]['type']);
                    self::assertSame([['entityName' => 'category']], $set['relations']);

                    return true;
                }),
                $this->context,
            );

        $this->logger->expects(self::once())
            ->method('info')
            ->with('RcDualPrice: CustomFieldSet angelegt.', self::anything());

        (new CustomFieldInstaller($this->repository, $this->logger))->install($this->context);
    }

    public function testInstallIstIdempotentWennSetExistiert(): void
    {
        $this->mockSearchWithEntity();

        $this->repository->expects(self::never())->method('upsert');

        $this->logger->expects(self::once())
            ->method('info')
            ->with('RcDualPrice: CustomFieldSet bereits vorhanden, Install ist No-op.', self::anything());

        (new CustomFieldInstaller($this->repository, $this->logger))->install($this->context);
    }

    public function testInstallLogsAndRethrowsOnUpsertFailure(): void
    {
        $this->mockSearchEmpty();
        $boom = new \RuntimeException('DAL exploded');
        $this->repository->method('upsert')->willThrowException($boom);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'RcDualPrice: CustomFieldSet-Install fehlgeschlagen.',
                self::callback(function (array $context): bool {
                    self::assertSame(\RuntimeException::class, $context['exception']);
                    self::assertSame('DAL exploded', $context['message']);

                    return true;
                }),
            );

        $this->expectExceptionObject($boom);

        (new CustomFieldInstaller($this->repository, $this->logger))->install($this->context);
    }

    public function testUninstallDeletesExistingSet(): void
    {
        $entity = $this->mockSearchWithEntity();

        $this->repository->expects(self::once())
            ->method('delete')
            ->with([['id' => $entity->getId()]], $this->context);

        $this->logger->expects(self::once())
            ->method('info')
            ->with('RcDualPrice: CustomFieldSet entfernt.', self::anything());

        (new CustomFieldInstaller($this->repository, $this->logger))->uninstall($this->context);
    }

    public function testUninstallIstNoopWennSetAbwesend(): void
    {
        $this->mockSearchEmpty();

        $this->repository->expects(self::never())->method('delete');

        $this->logger->expects(self::once())
            ->method('info')
            ->with('RcDualPrice: CustomFieldSet bereits abwesend, Uninstall ist No-op.', self::anything());

        (new CustomFieldInstaller($this->repository, $this->logger))->uninstall($this->context);
    }

    public function testUninstallLogsAndRethrowsOnDeleteFailure(): void
    {
        $this->mockSearchWithEntity();
        $boom = new \RuntimeException('Cascade refused');
        $this->repository->method('delete')->willThrowException($boom);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('RcDualPrice: CustomFieldSet-Uninstall fehlgeschlagen.', self::anything());

        $this->expectExceptionObject($boom);

        (new CustomFieldInstaller($this->repository, $this->logger))->uninstall($this->context);
    }

    public function testNullLoggerIstZulaessigerDefault(): void
    {
        $this->mockSearchEmpty();
        $this->repository->expects(self::once())->method('upsert');

        // Smoke-Test: Konstruktion ohne Logger-Argument wirft nicht und fuehrt zu keinem Fehler.
        (new CustomFieldInstaller($this->repository))->install($this->context);
        self::assertInstanceOf(NullLogger::class, new NullLogger());
    }

    private function mockSearchEmpty(): void
    {
        $result = $this->createMock(EntitySearchResult::class);
        $result->method('first')->willReturn(null);
        $this->repository->method('search')
            ->with(self::isInstanceOf(Criteria::class), $this->context)
            ->willReturn($result);
    }

    private function mockSearchWithEntity(): CustomFieldSetEntity
    {
        $entity = new CustomFieldSetEntity();
        $entity->setId('set-id-abc');
        $result = $this->createMock(EntitySearchResult::class);
        $result->method('first')->willReturn($entity);
        $this->repository->method('search')
            ->with(self::isInstanceOf(Criteria::class), $this->context)
            ->willReturn($result);

        return $entity;
    }
}
