<?php declare(strict_types=1);

namespace IranPay\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1572594722AddiranpayTransactionsTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572594721;
    }

    public function update(Connection $connection): void
    {
        $query = '
            CREATE TABLE IF NOT EXISTS `iranpay_transactions` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NULL,
                `order_transaction_id` BINARY(16) NULL,
                `iranpay_transaction_id` VARCHAR(255),
                `payment_id` INT(11) NULL,
                `paymentMethod` VARCHAR(255) NOT NULL,
                `amount` FLOAT NOT NULL,
                `currency` VARCHAR(3) NOT NULL,
                `exception` TEXT,
                `comment` VARCHAR(255),
                `dispatch` VARCHAR(255),
                `status` VARCHAR(255) NULL,
                `order_state_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),

                KEY `fk.iranpay_transaction.customer_id` (`customer_id`),
                KEY `fk.iranpay_transaction.order_id` (`order_id`),
                KEY `fk.iranpay_transaction.order_state_id` (`order_state_id`),

                CONSTRAINT `fk.iranpay_transaction.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.iranpay_transaction.order_id`
                    FOREIGN KEY (`order_id`)
                    REFERENCES `order` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.iranpay_transaction.order_state_id`
                    FOREIGN KEY (`order_state_id`)
                    REFERENCES `state_machine_state` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ';

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
