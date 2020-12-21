<?php declare(strict_types=1);

namespace IranPay\Entity;

use IranPay\Entity\IranPayTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(IranPayTransactionEntity $entity)
 * @method void              set(string $key, IranPayTransactionEntity $entity)
 * @method IranPayTransactionEntity[]    getIterator()
 * @method IranPayTransactionEntity[]    getElements()
 * @method IranPayTransactionEntity|null get(string $key)
 * @method IranPayTransactionEntity|null first()
 * @method IranPayTransactionEntity|null last()
 */
class IranPayTransactionEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return IranPayTransactionEntity::class;
    }
}
