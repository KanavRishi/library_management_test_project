<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240816094406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE isbn_value isbn_value VARCHAR(15) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331689C3DA9 ON book (isbn_value)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649803A19BB ON user (email_value)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_CBE5A331689C3DA9 ON book');
        $this->addSql('ALTER TABLE book CHANGE isbn_value isbn_value VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D649803A19BB ON user');
    }
}
