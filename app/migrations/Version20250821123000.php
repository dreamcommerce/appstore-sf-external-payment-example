<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250821123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shop_payment_methods table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SHOP_APP_INSTALLATIONS_SHOP ON shop_app_installations (shop)');
        $this->addSql('CREATE TABLE shop_payment_methods (
            id INT AUTO_INCREMENT NOT NULL,
            shop_id INT UNSIGNED NOT NULL,
            payment_method_id VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            CONSTRAINT FK_SHOP_PAYMENT_METHOD_SHOP_ID FOREIGN KEY (shop_id) REFERENCES shop_app_installations (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX idx_shop_payment_method ON shop_payment_methods (shop_id, payment_method_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shop_payment_methods');
    }
}
