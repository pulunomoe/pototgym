<?php

namespace Com\Pulunomoe\PototGym\Model\Trait;

use PDO;

trait DataTableTrait
{
    protected function getDataForTable(PDO $pdo, string $table, array $columns, string $keyword, string $order, string $dir, int $start, int $length, string $initial = '', array $initialParams = []): array
    {
        $start = filter_var($start, FILTER_SANITIZE_NUMBER_INT);
        $length = filter_var($length, FILTER_SANITIZE_NUMBER_INT);
        if ($keyword != '%') $keyword = '%' . $keyword . '%';
        $dir = $dir == 'asc' ? 'ASC' : 'DESC';

        $where = $this->createWhere($columns, $initial);
        $params = $this->createParams($columns, $keyword, $initialParams);

        return [
            'recordsTotal' => $this->getTotal($pdo, $table, $initial, $initialParams),
            'recordsFiltered' => $this->getFiltered($pdo, $table, $where, $params),
            'data' => $this->getData($pdo, $table, $where, $params, $order, $dir, $start, $length)
        ];
    }

    protected function createWhere(array $columns, string $initial = ''): string
    {
        $where = $initial;
        if (!empty($initial)) $where .= '(';
        for ($i = 0; $i < sizeof($columns); $i++) {
            $where .= $columns[$i] . ' LIKE ?';
            if ($i < sizeof($columns) - 1) {
                $where .= ' OR ';
            }
        }
        if (!empty($initial)) $where .= ' )';
        $where .= ' AND 1 = 1';

        return $where;
    }

    protected function createParams(array $columns, string $keyword, array $initial = []): array
    {
        $params = $initial;
        foreach ($columns as $ignored) {
            $params[] = $keyword;
        }

        return $params;
    }

    private function getTotal(PDO $pdo, string $table, string $initial, array $initialParams): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(id) FROM ' . $table. ' WHERE ' . $initial . ' 1=1');
        $stmt->execute($initialParams);
        return (int) $stmt->fetchColumn();
    }

    private function getFiltered(PDO $pdo, string $table, string $where, array $params): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(id) FROM ' . $table . ' WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function getData(PDO $pdo, string $table, string $where, array $params, string $order, string $dir, int $start, int $length): array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE ' . $where . ' ORDER BY ' . $order . ' ' . $dir . ' LIMIT ' . $start . ', ' . $length);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
