<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220126130233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE texture_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE texture (id INT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, price INT DEFAULT 0 NOT NULL, approved BOOLEAN DEFAULT \'false\' NOT NULL, category VARCHAR(255) NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82660D727E3C61F9 ON texture (owner_id)');
        $this->addSql('CREATE TABLE texture_purchase (user_id INT NOT NULL, texture_id INT NOT NULL, rating DOUBLE PRECISION NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(user_id, texture_id))');
        $this->addSql('CREATE INDEX IDX_B199A6D5A76ED395 ON texture_purchase (user_id)');
        $this->addSql('CREATE INDEX IDX_B199A6D5204BC3AC ON texture_purchase (texture_id)');
        $this->addSql('ALTER TABLE texture ADD CONSTRAINT FK_82660D727E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE texture_purchase ADD CONSTRAINT FK_B199A6D5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE texture_purchase ADD CONSTRAINT FK_B199A6D5204BC3AC FOREIGN KEY (texture_id) REFERENCES texture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE texture_purchase DROP CONSTRAINT FK_B199A6D5204BC3AC');
        $this->addSql('DROP SEQUENCE texture_id_seq CASCADE');
        $this->addSql('DROP TABLE texture');
        $this->addSql('DROP TABLE texture_purchase');
    }
}
