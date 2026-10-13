<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Invoice>
 */
class InvoiceRepository extends AbstractRepository
{
    private const ALLOWED_FILTER_FIELDS = [
        'id',
        'number',
        'contract',
        'issued',
        'paidAt',
        'priceEur',
        'quantity',
        'amountEur',
        'amountUahExclVat',
        'exchangeRate',
        'vatAmount',
        'amountUah',
        'nomenclature',
    ];

    private const ALLOWED_SORT_FIELDS = [
        'id',
        'number',
        'contract',
        'issued',
        'paidAt',
        'priceEur',
        'quantity',
        'amountEur',
        'amountUahExclVat',
        'exchangeRate',
        'vatAmount',
        'amountUah',
        'nomenclature',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function getAllowedFilterFields(): array
    {
        return self::ALLOWED_FILTER_FIELDS;
    }

    public function getAllowedSortFields(): array
    {
        return self::ALLOWED_SORT_FIELDS;
    }
}
