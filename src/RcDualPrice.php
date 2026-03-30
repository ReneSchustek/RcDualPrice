<?php declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice;

use Ruhrcoder\RcDualPrice\Core\System\CustomField\CustomFieldInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

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
        return new CustomFieldInstaller(
            $this->container->get('custom_field_set.repository')
        );
    }
}
