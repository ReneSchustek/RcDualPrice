<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ruhrcoder\RcDualPrice\Core\System\CustomField\CustomFieldInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;

final class RcDualPrice extends Plugin
{
    public function install(InstallContext $context): void
    {
        parent::install($context);
        $this->getInstaller()->install($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        parent::update($context);
        $this->getInstaller()->install($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if (!$context->keepUserData()) {
            $this->getInstaller()->uninstall($context->getContext());
        }
    }

    private function getInstaller(): CustomFieldInstaller
    {
        // `isset()` statt `=== null`: `Bundle::$container` ist eine typisierte Eigenschaft ohne
        // Vorbelegung. Ist sie nie gesetzt worden, wirft schon der **Zugriff** einen
        // `Error: Typed property … must not be accessed before initialization` — die Prüfung
        // darunter kam nie zum Zuge und war toter Code. Wer den Fehler dann las, suchte in
        // Symfonys Bundle-Klasse statt in der Aufrufreihenfolge des Lebenszyklus.
        if (!isset($this->container)) {
            throw new \RuntimeException('Plugin container is not available.');
        }

        $container = $this->container;

        /** @var EntityRepository<CustomFieldSetCollection> $repository */
        $repository = $container->get('custom_field_set.repository');

        // Logger ist im Lifecycle-Container nicht garantiert — Fallback auf NullLogger,
        // damit Lifecycle-Pfade ohne Log-Infrastruktur weiterhin laufen.
        $logger = $container->has('logger') ? $container->get('logger') : new NullLogger();
        if (!$logger instanceof LoggerInterface) {
            $logger = new NullLogger();
        }

        return new CustomFieldInstaller($repository, $logger);
    }
}
