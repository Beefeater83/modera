<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Model\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PageArgumentResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Page::class !== $argument->getType()) {
            return [];
        }

        $page = Page::DEFAULT_PAGE;
        $pageSize = Page::DEFAULT_PAGE_SIZE;

        if ($request->query->has('page')) {
            $rawPage = $request->query->all()['page'] ?? $request->query->get('page');
            if (!is_scalar($rawPage) || !is_numeric($rawPage) || (int) $rawPage < Page::MIN_PAGE) {
                throw new BadRequestHttpException(sprintf('The "page" parameter must be a positive integer (minimum %d).', Page::MIN_PAGE));
            }
            $page = (int) $rawPage;
        }

        if ($request->query->has('pageSize')) {
            $rawPageSize = $request->query->all()['pageSize'] ?? $request->query->get('pageSize');
            if (!is_scalar($rawPageSize) || !is_numeric($rawPageSize) || (int) $rawPageSize < Page::MIN_PAGE_SIZE || (int) $rawPageSize > Page::MAX_PAGE_SIZE) {
                throw new BadRequestHttpException(sprintf('The "pageSize" parameter must be an integer between %d and %d.', Page::MIN_PAGE_SIZE, Page::MAX_PAGE_SIZE));
            }
            $pageSize = (int) $rawPageSize;
        }

        return [new Page($page, $pageSize)];
    }
}
