<?php
declare(strict_types=1);

namespace IranPay;

use Doctrine\DBAL\Connection;
use IranPay\Helpers\AddPaymentMethods;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * Class IranPay
 * @package IranPay
 * @author Hossein Haghparast
 * @data Dec 16 2020
 * @version Beta
 * @description plugin to add 4 payment method with custom token ability
 */
class IranPay extends Plugin
{

    /**
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void
    {
        (new AddPaymentMethods($installContext->getContext(), $this->container))->addAllMethods();
        parent::install($installContext);
    }

    /**
     * @param ActivateContext $activateContext
     */
    public function activate(ActivateContext $activateContext): void
    {
        (new AddPaymentMethods($activateContext->getContext(), $this->container))->changeMethodStatus(true);
        parent::activate($activateContext);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context): void
    {
        (new AddPaymentMethods($context->getContext(), $this->container))->changeMethodStatus(false);
        parent::deactivate($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        (new AddPaymentMethods($context->getContext(), $this->container))->changeMethodStatus(false);

        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        $connection->executeQuery('DROP TABLE IF EXISTS `iranpay_transactions`');
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');

        parent::uninstall($context);
    }


}
