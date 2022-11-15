<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220304140611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase ALTER rating TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE purchase ALTER rating DROP DEFAULT');
        $this->addSql('ALTER TABLE texture_purchase ALTER rating TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE texture_purchase ALTER rating DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE texture_purchase ALTER rating TYPE INT');
        $this->addSql('ALTER TABLE texture_purchase ALTER rating DROP DEFAULT');
        $this->addSql('ALTER TABLE purchase ALTER rating TYPE INT');
        $this->addSql('ALTER TABLE purchase ALTER rating DROP DEFAULT');
    }
}
