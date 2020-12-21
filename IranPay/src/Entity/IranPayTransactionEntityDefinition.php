<?php declare(strict_types=1);

namespace IranPay\Entity;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;

/**
 * Class IranPayTransactionEntityDefinition
 * @package IranPay\Entity
 * @author Hossein Haghparast
 */
class IranPayTransactionEntityDefinition extends EntityDefinition
{
    /**
     *
     */
    public const ENTITY_NAME = 'iranpay_transactions';

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return IranPayTransactionEntityCollection::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return IranPayTransactionEntity::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class))
                ->addFlags(new Required()),
            (new FkField('order_id', 'orderId', OrderDefinition::class))
                ->addFlags(new Required()),
            (new FkField('order_transaction_id', 'orderTransactionId', OrderTransactionDefinition::class))
                ->addFlags(new Required()),
            (new FkField('order_state_id', 'orderStateId', StateMachineStateDefinition::class))
                ->addFlags(new Required()),

            (new StringField('iranpay_transaction_id', 'iranpayTransactionId')),
            (new IntField('payment_id', 'paymentId')),
            (new FloatField('amount', 'amount'))->setFlags(new Required()),
            (new StringField('currency', 'currency', 3))->setFlags(new Required()),
//            (new StringField('latest_action_name', 'latestActionName'))->setFlags(new Required()),
            (new LongTextField('exception', 'exception')),
            (new StringField('comment', 'comment')),
            (new StringField('status', 'status')),
            (new StringField('paymentMethod', 'paymentMethod')),
            (new StringField('dispatch', 'dispatch')),
//            (new IntField('state_id', 'stateId')),

            new ManyToOneAssociationField(
                'order',
                'order_id',
                OrderDefinition::class,
                'id'
            ),
            new ManyToOneAssociationField(
                'customer',
                'customer_id',
                CustomerDefinition::class,
                'id',
                true
            ),
            new ManyToOneAssociationField(
                'stateMachineState',
                'order_state_id',
                StateMachineStateDefinition::class,
                'id',
                true
            ),
            new OneToOneAssociationField(
                'orderTransaction',
                'order_transaction_id',
                'id',
                OrderTransactionDefinition::class,
                true
            ),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
