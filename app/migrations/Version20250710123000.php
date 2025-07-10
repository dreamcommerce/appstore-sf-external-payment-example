<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250710123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shop_app_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shop_app_tokens (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            shop_app_installation_id INT UNSIGNED NOT NULL,
            access_token VARCHAR(255) NOT NULL,
            refresh_token VARCHAR(255) DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_shop_app_installation_id (shop_app_installation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE shop_app_tokens 
            ADD CONSTRAINT FK_shop_tokens_shop_app_installations 
            FOREIGN KEY (shop_app_installation_id) 
            REFERENCES shop_app_installations (id) 
            ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shop_app_tokens');
    }
}
