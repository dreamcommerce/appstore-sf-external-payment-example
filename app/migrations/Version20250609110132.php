<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250609110132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shops_installed table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shop_app_installations (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            shop VARCHAR(100) NOT NULL,
            shop_url VARCHAR(255) NOT NULL,
            application_version INT NOT NULL,
            auth_code VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shops_installed');
    }
}
