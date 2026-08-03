<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcDualPrice\RcDualPrice;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Der Lebenszyklus des Plugins — Installieren, Aktualisieren, Deinstallieren.
 *
 * Diese drei Pfade laufen genau einmal und dann nie wieder; einen Fehler darin sieht man erst,
 * wenn ein Kunde das Plugin aufspielt. Sie legen das Zusatzfeld an, an dem die ganze Funktion
 * hängt: Ohne `rc_dual_price_active` an der Kategorie zeigt der Shop nirgends einen Zweitpreis.
 *
 * Beim Deinstallieren entscheidet eine einzige Bedingung über Datenverlust.
 */
class RcDualPriceTest extends TestCase
{
    /**
     * Was: Installation.
     * Warum: Sie legt das Zusatzfeld an. Bleibt es aus, ist das Plugin installiert und wirkungslos.
     * Erwartet: Das Feld-Repository wird angefasst.
     */
    public function testInstallCreatesTheCustomField(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::atLeastOnce())->method('upsert');

        $plugin = $this->pluginWithContainer($repository);
        $plugin->install($this->createConfiguredMock(InstallContext::class, [
            'getContext' => \Shopware\Core\Framework\Context::createDefaultContext(),
        ]));
    }

    /**
     * Was: Aktualisierung.
     * Warum: Sie legt dasselbe Feld an — mit Absicht. Wer von einer Fassung kommt, in der das Feld
     *        noch nicht existierte oder von Hand gelöscht wurde, bekommt es beim Update zurück.
     * Erwartet: Das Feld-Repository wird angefasst.
     */
    public function testUpdateCreatesTheCustomFieldAgain(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::atLeastOnce())->method('upsert');

        $plugin = $this->pluginWithContainer($repository);
        $plugin->update($this->createConfiguredMock(UpdateContext::class, [
            'getContext' => \Shopware\Core\Framework\Context::createDefaultContext(),
        ]));
    }

    /**
     * Was: Deinstallation, bei der die Daten behalten werden sollen.
     * Warum: Das ist der Fall bei jedem Update, das intern deinstalliert und neu installiert.
     *        Würde das Feld dabei gelöscht, verlöre der Shop die Zuordnung **aller** Kategorien —
     *        und niemand könnte sagen, welche vorher einen Zweitpreis zeigten.
     * Erwartet: Nichts wird gelöscht.
     */
    public function testKeepingUserDataLeavesTheCustomFieldAlone(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::never())->method('delete');
        $repository->expects(self::never())->method('search');

        $plugin = $this->pluginWithContainer($repository);
        $plugin->uninstall($this->createConfiguredMock(UninstallContext::class, [
            'keepUserData' => true,
            'getContext' => \Shopware\Core\Framework\Context::createDefaultContext(),
        ]));
    }

    /**
     * Was: Die Plugin-Klasse ohne Container.
     * Warum: Im Lebenszyklus ist der Container nicht immer aufgebaut. Ein stiller Fehlschlag wäre
     *        hier schlimmer als ein lauter — deshalb wirft die Klasse ausdrücklich.
     * Erwartet: eine Ausnahme mit klarer Aussage.
     */
    public function testAMissingContainerFailsLoudly(): void
    {
        $plugin = new RcDualPrice(true, __DIR__);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('container');

        $plugin->install($this->createConfiguredMock(InstallContext::class, [
            'getContext' => \Shopware\Core\Framework\Context::createDefaultContext(),
        ]));
    }

    /** @param EntityRepository<CustomFieldSetCollection> $repository */
    private function pluginWithContainer(EntityRepository $repository): RcDualPrice
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            static fn (string $id): mixed => $id === 'custom_field_set.repository' ? $repository : null,
        );
        $container->method('has')->willReturn(false);

        $plugin = new RcDualPrice(true, __DIR__);
        $plugin->setContainer($container);

        return $plugin;
    }
}
