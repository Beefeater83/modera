<?php

declare(strict_types=1);

namespace App\Model;

class Page
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PAGE_SIZE = 25;
    public const MAX_PAGE_SIZE = 100;
    public const MIN_PAGE = 1;
    public const MIN_PAGE_SIZE = 1;

    private int $page;
    private int $pageSize;

    public function __construct(int $page = self::DEFAULT_PAGE, int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->page = max(self::MIN_PAGE, $page);
        $this->pageSize = min(self::MAX_PAGE_SIZE, max(self::MIN_PAGE_SIZE, $pageSize));
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    public function getLimit(): int
    {
        return $this->pageSize;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPage(int $page): static
    {
        $this->page = max(self::MIN_PAGE, $page);

        return $this;
    }

    public function setPageSize(int $pageSize): static
    {
        $this->pageSize = min(self::MAX_PAGE_SIZE, max(self::MIN_PAGE_SIZE, $pageSize));

        return $this;
    }
}
