<?php

namespace Com\Pulunomoe\PototGym\Model\Interface;

interface FindAllForDataTableInterface
{
    public function findAllForDataTable(int $start, int $length, string $keyword, int $order, string $dir, array $filters = []): array;
}
