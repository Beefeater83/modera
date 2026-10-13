<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Contract>
 */
class ContractRepository extends AbstractRepository
{
    private const ALLOWED_FILTER_FIELDS = [
        'id',
        'customer',
        'number',
        'issued',
        'signed',
        'amountEur',
        'licencePriceEur',
        'licenceSince',
        'subject',
    ];

    private const ALLOWED_SORT_FIELDS = [
        'id',
        'customer',
        'number',
        'issued',
        'signed',
        'amountEur',
        'licencePriceEur',
        'licenceSince',
        'subject',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
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
