<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Passkey;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Passkey>
 */
class PasskeyRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Passkey::class);
    }

    public function findOneByCredentialId(string $credentialId): Passkey
    {
        $passkey = $this->findOneBy(['credentialId' => $credentialId]);
        if (!$passkey) {
            throw new ResourceNotFoundException(Passkey::class, $credentialId);
        }

        return $passkey;
    }

    /**
     * @return Passkey[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'ASC']);
    }

    public function getAllowedFilterFields(): array
    {
        return [];
    }

    public function getAllowedSortFields(): array
    {
        return [];
    }
}
