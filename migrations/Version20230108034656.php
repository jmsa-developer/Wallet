<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230108034656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD amount NUMERIC(8, 2) NOT NULL');
        $this->addSql('ALTER TABLE `order` RENAME INDEX idx_f5299398dc2902e0 TO IDX_F529939819EB6921');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP amount');
        $this->addSql('ALTER TABLE `order` RENAME INDEX idx_f529939819eb6921 TO IDX_F5299398DC2902E0');
    }
}
