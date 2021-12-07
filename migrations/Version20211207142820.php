<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211207142820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE model_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE model (id INT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, rating SMALLINT DEFAULT 5 NOT NULL, created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D79572D97E3C61F9 ON model (owner_id)');
        $this->addSql('CREATE TABLE user_model (user_id INT NOT NULL, model_id INT NOT NULL, PRIMARY KEY(user_id, model_id))');
        $this->addSql('CREATE INDEX IDX_35578981A76ED395 ON user_model (user_id)');
        $this->addSql('CREATE INDEX IDX_355789817975B7E7 ON user_model (model_id)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D97E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_model ADD CONSTRAINT FK_35578981A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_model ADD CONSTRAINT FK_355789817975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD created_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD updated_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_model DROP CONSTRAINT FK_355789817975B7E7');
        $this->addSql('DROP SEQUENCE model_id_seq CASCADE');
        $this->addSql('DROP TABLE model');
        $this->addSql('DROP TABLE user_model');
        $this->addSql('ALTER TABLE "user" DROP created_on');
        $this->addSql('ALTER TABLE "user" DROP updated_on');
    }
}
