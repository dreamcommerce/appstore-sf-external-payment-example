<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911152500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create transactions table for webhook payment data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE transactions (
            id INT AUTO_INCREMENT NOT NULL,
            payment_method_id VARCHAR(36) NOT NULL,
            type VARCHAR(50) NOT NULL,
            order_id VARCHAR(255) DEFAULT NULL,
            external_payment_id VARCHAR(255) DEFAULT NULL,
            external_transaction_id VARCHAR(255) NOT NULL,
            refund_id VARCHAR(255) DEFAULT NULL,
            currency_id VARCHAR(10) NOT NULL,
            currency_value NUMERIC(10, 2) NOT NULL,
            payment_data JSON DEFAULT NULL,
            payment_success_shop_link VARCHAR(500) DEFAULT NULL,
            payment_fail_shop_link VARCHAR(500) DEFAULT NULL,
            status VARCHAR(50) DEFAULT NULL,
            comment LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            transaction_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            CONSTRAINT FK_TRANSACTION_PAYMENT_METHOD FOREIGN KEY (payment_method_id) REFERENCES shop_payment_methods (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX idx_transaction_payment_method ON transactions (payment_method_id)');
        $this->addSql('CREATE INDEX idx_transaction_external_id ON transactions (external_transaction_id)');
        $this->addSql('CREATE INDEX idx_transaction_type ON transactions (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transactions');
    }
}
