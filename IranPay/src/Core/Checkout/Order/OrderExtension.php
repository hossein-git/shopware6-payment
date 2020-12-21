<?php

namespace IranPay\Core\Checkout\Order;

use IranPay\Entity\IranPayTransactionEntityDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class OrderExtension
 * @package IranPay\Core\Checkout\Order
 */
class OrderExtension extends EntityExtension
{
    /**
     * @param FieldCollection $collection
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'iranpayTransactions',
                IranPayTransactionEntityDefinition::class,
                'order_id'
            )
        );
    }

    /**
     * @return string
     */
    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
