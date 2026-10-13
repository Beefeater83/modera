<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Model\Filter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FilterArgumentResolver implements ValueResolverInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Filter::class !== $argument->getType()) {
            return [];
        }

        if (!$request->query->has('filter')) {
            return [new Filter([])];
        }

        $allQuery = $request->query->all();
        $queryString = $allQuery['filter'] ?? null;

        if (!is_array($queryString)) {
            $this->logger->warning('Invalid filter parameter. Expected array, got: ' . gettype($queryString));
            throw new BadRequestHttpException('The "filter" parameter must be an array.');
        }

        return [new Filter($queryString)];
    }
}
