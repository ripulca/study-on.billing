<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230515214400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE course_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE course (id INT NOT NULL, code VARCHAR(255) NOT NULL, type SMALLINT NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE transaction (id INT NOT NULL, customer_id INT NOT NULL, course_id INT DEFAULT NULL, type SMALLINT NOT NULL, amount DOUBLE PRECISION NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D19395C3F3 ON transaction (customer_id)');
        $this->addSql('CREATE INDEX IDX_723705D1591CC992 ON transaction (course_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19395C3F3 FOREIGN KEY (customer_id) REFERENCES billing_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE course_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE transaction_id_seq CASCADE');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D19395C3F3');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1591CC992');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE transaction');
    }
}
