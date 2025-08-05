<?php declare(strict_types=1);

namespace RcDualPrice;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class RcDualPrice extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->addCustomFields($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->addCustomFields($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        // Cleanup: Try to remove any existing custom fields
        if (!$uninstallContext->keepUserData()) {
            $this->cleanupCustomFields($uninstallContext);
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    private function addCustomFields(InstallContext|UpdateContext $context): void
    {
        $installer = new \RcDualPrice\Core\System\CustomField\CustomFieldInstaller(
            $this->container->get('custom_field_set.repository')
        );
        $installer->install();
    }

    private function cleanupCustomFields(UninstallContext $context): void
    {
        try {
            $installer = new \RcDualPrice\Core\System\CustomField\CustomFieldInstaller(
                $this->container->get('custom_field_set.repository')
            );
            $installer->uninstall();
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
}