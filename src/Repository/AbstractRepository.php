<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ResourceNotFoundException;
use App\Model\Filter;
use App\Model\Page;
use App\Model\PaginatedResult;
use App\Model\Sort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    protected const ALLOWED_OPERATORS = [
        'eq',
        'neq',
        'like',
        'gt',
        'gte',
        'lt',
        'lte',
        'in',
        'nin',
    ];

    /**
     * @var class-string<T>
     */
    private string $entityClass;

    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
        $this->entityClass = $entityClass;
    }

    /**
     * @return string[]
     */
    abstract public function getAllowedFilterFields(): array;

    /**
     * @return string[]
     */
    abstract public function getAllowedSortFields(): array;

    public function countAll(): int
    {
        return $this->count([]);
    }

    protected function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        $allowedFilterFields = $this->getAllowedFilterFields();
        $paramIndex = 0;

        foreach ($criteria as $field => $filters) {
            if (!in_array($field, $allowedFilterFields, true)) {
                throw new BadRequestHttpException(sprintf('Filtering by field "%s" is not allowed.', $field));
            }

            if (!is_array($filters)) {
                throw new BadRequestHttpException(sprintf('Filter for field "%s" must be an associative array of operators.', $field));
            }

            foreach ($filters as $operator => $value) {
                if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
                    throw new BadRequestHttpException(sprintf('Filter operator "%s" is not supported for field "%s".', $operator, $field));
                }

                $paramIndex++;
                $paramName = sprintf('p_%s_%s_%d', str_replace('.', '_', (string) $field), (string) $operator, $paramIndex);
                $paramValue = $value;
                $setParameter = true;

                switch ($operator) {
                    case 'eq':
                        if ($value !== null) {
                            if (is_array($value)) {
                                throw new BadRequestHttpException(sprintf('Filter value for operator "eq" on field "%s" must be a scalar.', $field));
                            }
                            $qb->andWhere("entity.$field = :$paramName");
                        } else {
                            $qb->andWhere("entity.$field IS NULL");
                            $setParameter = false;
                        }
                        break;

                    case 'neq':
                        if ($value !== null) {
                            if (is_array($value)) {
                                throw new BadRequestHttpException(sprintf('Filter value for operator "neq" on field "%s" must be a scalar.', $field));
                            }
                            $qb->andWhere("entity.$field <> :$paramName");
                        } else {
                            $qb->andWhere("entity.$field IS NOT NULL");
                            $setParameter = false;
                        }
                        break;

                    case 'like':
                        if ($value === null || is_array($value)) {
                            throw new BadRequestHttpException(sprintf('Filter value for operator "like" on field "%s" must be a non-null scalar.', $field));
                        }
                        $qb->andWhere("entity.$field LIKE :$paramName");
                        $paramValue = '%' . $value . '%';
                        break;

                    case 'gt':
                    case 'gte':
                    case 'lt':
                    case 'lte':
                        if ($value === null || is_array($value)) {
                            throw new BadRequestHttpException(sprintf('Filter value for operator "%s" on field "%s" must be a non-null scalar.', $operator, $field));
                        }
                        $symbol = match ($operator) {
                            'gt' => '>',
                            'gte' => '>=',
                            'lt' => '<',
                            'lte' => '<=',
                        };
                        $qb->andWhere("entity.$field $symbol :$paramName");
                        break;

                    case 'in':
                        $values = is_array($value) ? array_values($value) : [$value];
                        if ($values === []) {
                            throw new BadRequestHttpException(sprintf('Filter "%s" with operator "in" must not be empty.', $field));
                        }
                        $nonNullValues = array_values(array_filter($values, static fn($v) => $v !== null));
                        $hasNull = count($nonNullValues) < count($values);

                        if (!empty($nonNullValues) && $hasNull) {
                            $qb->andWhere("(entity.$field IN (:$paramName) OR entity.$field IS NULL)");
                            $paramValue = $nonNullValues;
                        } elseif (!empty($nonNullValues)) {
                            $qb->andWhere("entity.$field IN (:$paramName)");
                            $paramValue = $nonNullValues;
                        } else {
                            $qb->andWhere("entity.$field IS NULL");
                            $setParameter = false;
                        }
                        break;

                    case 'nin':
                        $values = is_array($value) ? array_values($value) : [$value];
                        if ($values === []) {
                            throw new BadRequestHttpException(sprintf('Filter "%s" with operator "nin" must not be empty.', $field));
                        }
                        $nonNullValues = array_values(array_filter($values, static fn($v) => $v !== null));
                        $hasNull = count($nonNullValues) < count($values);

                        if (!empty($nonNullValues) && $hasNull) {
                            $qb->andWhere("(entity.$field NOT IN (:$paramName) AND entity.$field IS NOT NULL)");
                            $paramValue = $nonNullValues;
                        } elseif (!empty($nonNullValues)) {
                            $qb->andWhere("(entity.$field NOT IN (:$paramName) OR entity.$field IS NULL)");
                            $paramValue = $nonNullValues;
                        } else {
                            $qb->andWhere("entity.$field IS NOT NULL");
                            $setParameter = false;
                        }
                        break;
                }

                if ($setParameter) {
                    $type = $paramValue instanceof Uuid ? 'uuid' : null;
                    $qb->setParameter($paramName, $paramValue, $type);
                }
            }
        }
    }

    protected function applyOrderBy(QueryBuilder $qb, array $orderBy): void
    {
        $allowedSortFields = $this->getAllowedSortFields();

        foreach ($orderBy as $field => $direction) {
            if (!in_array($field, $allowedSortFields, true)) {
                throw new BadRequestHttpException(sprintf('Sorting by field "%s" is not allowed.', $field));
            }

            $direction = strtoupper((string) $direction);
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw new BadRequestHttpException(sprintf('Invalid sort direction "%s".', $direction));
            }

            $qb->addOrderBy("entity.$field", $direction);
        }
    }

    public function countByCriteria(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('entity')
            ->select('COUNT(entity.id)');

        $this->applyCriteria($qb, $criteria);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return T[]
     */
    public function findPaginated(array $criteria = [], array $orderBy = [], int $offset = 0, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('entity');

        $this->applyCriteria($qb, $criteria);
        $this->applyOrderBy($qb, $orderBy);

        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getPaginated(Filter $filter, Sort $sort, Page $page): PaginatedResult
    {
        $criteria = $filter->getCriteria();
        $orderBy = $sort->getOrderBy();
        $total = $this->countByCriteria($criteria);
        $items = $this->findPaginated($criteria, $orderBy, $page->getOffset(), $page->getLimit());

        return new PaginatedResult($items, $page->getPage(), $page->getPageSize(), $total);
    }

    /**
     * @return T
     */
    public function getOrFail(mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object
    {
        $entity = parent::find($id, $lockMode, $lockVersion);
        if ($entity === null) {
            throw new ResourceNotFoundException($this->entityClass, $id);
        }

        return $entity;
    }
}
