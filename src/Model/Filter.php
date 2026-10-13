<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Filter
{
    public const FILTER_VALUE_NULL = 'none';
    public const FILTER_VALUE_TRUE = 'true';
    public const FILTER_VALUE_FALSE = 'false';

    private array $criteria = [];

    public function __construct(array $queryString = [])
    {
        $this->parseFilters($queryString);
    }

    private function parseFilters(array $queryString): void
    {
        $this->criteria = [];

        foreach ($queryString as $field => $filters) {
            $fieldName = (string) $field;
            if (trim($fieldName) === '') {
                throw new BadRequestHttpException('Filter field name cannot be empty.');
            }

            if (!is_array($filters)) {
                $this->criteria[$fieldName]['eq'] = $this->normalizeValue($filters);
                continue;
            }

            foreach ($filters as $operator => $value) {
                $operatorName = (string) $operator;
                if (trim($operatorName) === '') {
                    throw new BadRequestHttpException("Filter operator cannot be empty for field '{$fieldName}'.");
                }

                if (!is_array($value)) {
                    $this->criteria[$fieldName][$operatorName] = $this->normalizeValue($value);
                    continue;
                }

                if (empty($value) || is_numeric(key($value))) {
                    foreach ($value as &$subValue) {
                        $subValue = $this->normalizeValue($subValue);
                    }
                    unset($subValue);

                    if ($this->allBoolValues($value)) {
                        continue;
                    }

                    $this->criteria[$fieldName][$operatorName] = $value;
                    continue;
                }

                throw new BadRequestHttpException("Invalid structure for filter field: '{$fieldName}', operator: '{$operatorName}'.");
            }
        }
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function setCriteria(string $key, mixed $value): void
    {
        $this->criteria[$key] = $value;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($this->isNullValue($value)) {
            return null;
        }
        if ($this->isTrueValue($value)) {
            return true;
        }
        if ($this->isFalseValue($value)) {
            return false;
        }

        return $value;
    }

    protected function isNullValue(mixed $value): bool
    {
        return $value === self::FILTER_VALUE_NULL;
    }

    protected function isTrueValue(mixed $value): bool
    {
        return $value === self::FILTER_VALUE_TRUE || $value === true;
    }

    protected function isFalseValue(mixed $value): bool
    {
        return $value === self::FILTER_VALUE_FALSE || $value === false;
    }

    protected function allBoolValues(array $values): bool
    {
        return count($values) === 2
            && in_array(true, $values, true)
            && in_array(false, $values, true);
    }
}
