<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211215123917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tag (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tag_model (tag_id INT NOT NULL, model_id INT NOT NULL, PRIMARY KEY(tag_id, model_id))');
        $this->addSql('CREATE INDEX IDX_F5796BDFBAD26311 ON tag_model (tag_id)');
        $this->addSql('CREATE INDEX IDX_F5796BDF7975B7E7 ON tag_model (model_id)');
        $this->addSql('ALTER TABLE tag_model ADD CONSTRAINT FK_F5796BDFBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_model ADD CONSTRAINT FK_F5796BDF7975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE tag_model DROP CONSTRAINT FK_F5796BDFBAD26311');
        $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tag_model');
    }
}
