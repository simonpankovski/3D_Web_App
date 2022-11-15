<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211227133203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE model_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE model (id INT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, extension VARCHAR(255) NOT NULL, price INT DEFAULT 0 NOT NULL, approved BOOLEAN DEFAULT \'false\' NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D79572D97E3C61F9 ON model (owner_id)');
        $this->addSql('CREATE TABLE purchase (user_id INT NOT NULL, model_id INT NOT NULL, rating DOUBLE PRECISION NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(user_id, model_id))');
        $this->addSql('CREATE INDEX IDX_6117D13BA76ED395 ON purchase (user_id)');
        $this->addSql('CREATE INDEX IDX_6117D13B7975B7E7 ON purchase (model_id)');
        $this->addSql('CREATE TABLE tag (id INT NOT NULL, name VARCHAR(255) NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_389B7835E237E06 ON tag (name)');
        $this->addSql('CREATE TABLE tag_model (tag_id INT NOT NULL, model_id INT NOT NULL, PRIMARY KEY(tag_id, model_id))');
        $this->addSql('CREATE INDEX IDX_F5796BDFBAD26311 ON tag_model (tag_id)');
        $this->addSql('CREATE INDEX IDX_F5796BDF7975B7E7 ON tag_model (model_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, is_verified BOOLEAN DEFAULT \'false\' NOT NULL, password VARCHAR(255) NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D97E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13B7975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_model ADD CONSTRAINT FK_F5796BDFBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_model ADD CONSTRAINT FK_F5796BDF7975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE purchase DROP CONSTRAINT FK_6117D13B7975B7E7');
        $this->addSql('ALTER TABLE tag_model DROP CONSTRAINT FK_F5796BDF7975B7E7');
        $this->addSql('ALTER TABLE tag_model DROP CONSTRAINT FK_F5796BDFBAD26311');
        $this->addSql('ALTER TABLE model DROP CONSTRAINT FK_D79572D97E3C61F9');
        $this->addSql('ALTER TABLE purchase DROP CONSTRAINT FK_6117D13BA76ED395');
        $this->addSql('DROP SEQUENCE model_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE model');
        $this->addSql('DROP TABLE purchase');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tag_model');
        $this->addSql('DROP TABLE "user"');
    }
}
