<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Contract and Invoice tables, indexes, and foreign key constraint.
 */
final class Version20261012122135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contract and invoice tables with indexes and foreign key constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contract (
            id INT AUTO_INCREMENT NOT NULL,
            customer VARCHAR(255) NOT NULL,
            number VARCHAR(255) NOT NULL,
            issued DATE NOT NULL,
            signed DATE DEFAULT NULL,
            amount_eur NUMERIC(14, 2) NOT NULL,
            licence_price_eur NUMERIC(14, 2) DEFAULT NULL,
            licence_since DATE DEFAULT NULL,
            subject VARCHAR(255) NOT NULL,
            note LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE invoice (
            id INT AUTO_INCREMENT NOT NULL,
            number VARCHAR(255) NOT NULL,
            issued DATE NOT NULL,
            price_eur NUMERIC(14, 2) NOT NULL,
            quantity NUMERIC(10, 1) NOT NULL,
            amount_eur NUMERIC(14, 2) NOT NULL,
            amount_uah_excl_vat NUMERIC(14, 2) NOT NULL,
            exchange_rate NUMERIC(14, 2) NOT NULL,
            vat_amount NUMERIC(14, 2) NOT NULL,
            amount_uah NUMERIC(14, 2) NOT NULL,
            paid_at DATE DEFAULT NULL,
            nomenclature VARCHAR(255) NOT NULL,
            note LONGTEXT DEFAULT NULL,
            contract_id INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517442576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517442576E0FD');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE contract');
    }
}
