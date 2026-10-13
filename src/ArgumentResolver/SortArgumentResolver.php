<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Model\Sort;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SortArgumentResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Sort::class !== $argument->getType()) {
            return [];
        }

        if (!$request->query->has('sort')) {
            return [new Sort('')];
        }

        $allQuery = $request->query->all();
        $sort = $allQuery['sort'] ?? null;

        if (!is_string($sort)) {
            throw new BadRequestHttpException('The "sort" parameter must be a string.');
        }

        return [new Sort($sort)];
    }
}
