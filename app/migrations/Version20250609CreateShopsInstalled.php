<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250609CreateShopsInstalled extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shops_installed table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shops_installed (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            shop VARCHAR(100) NOT NULL,
            shop_url VARCHAR(255) NOT NULL,
            application_version INT NOT NULL,
            auth_code VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            tokens LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8_general_ci` ENGINE = InnoDB;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shops_installed');
    }
}

