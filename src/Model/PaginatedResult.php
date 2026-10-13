<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class PaginatedResult implements JsonSerializable
{
    private array $items;
    private int $page;
    private int $pageSize;
    private int $total;

    public function __construct(array $items, int $page, int $pageSize, int $total)
    {
        $this->items = $items;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->total = $total;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(static fn($item) => $item instanceof JsonSerializable ? $item->jsonSerialize() : $item, $this->items),
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'total' => $this->total,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
