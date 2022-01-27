<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220127161308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model ADD purchase_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE model ADD rating INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE purchase DROP rating');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE purchase ADD rating DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE model DROP purchase_count');
        $this->addSql('ALTER TABLE model DROP rating');
    }
}
