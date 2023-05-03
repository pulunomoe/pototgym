<?php

namespace Com\Pulunomoe\PototGym\Model;

use Com\Pulunomoe\PototGym\Model\Interface\FindAllForDataTableInterface;
use Com\Pulunomoe\PototGym\Model\Interface\FindOneForUserInterface;
use Com\Pulunomoe\PototGym\Model\Trait\DataTableTrait;

class CardioModel extends Model implements FindAllForDataTableInterface, FindOneForUserInterface
{
    use DataTableTrait;

    public function findAllForDataTable(int $start, int $length, string $keyword, int $order, string $dir, array $filters = []): array
    {
        $table = 'cardios';
        $columns = ['date', 'name', 'time', 'description'];
        $order = $columns[$order];

        $initial = 'user_id = ? AND ';
        $initialParams = [$filters['user_id']];

        return $this->getDataForTable($this->pdo, $table, $columns, $keyword, $order, $dir, $start, $length, $initial, $initialParams);
    }

    public function findOne(string $code, int $userId): array|bool
    {
        $stmt = $this->prepare('SELECT * FROM cardios WHERE code = ? AND user_id = ?');
        $stmt->execute([$code, $userId]);
        return $stmt->fetch();
    }

    public function create(int $userId, ?string $date, ?string $name, ?string $time, ?string $calories, ?string $description): ?array
    {
        $errors = [];

        if (empty($date) || !strtotime($date)) $errors[] = 'date is required';
        if (empty($name)) $errors[] = 'exercise name is required';
        if (!is_numeric($time)) $errors[] = 'exercise time is required';
        if (!is_numeric($calories)) $errors[] = 'exercise calories is required';

        if (!empty($errors)) return $errors;

        $stmt = $this->prepare('INSERT INTO cardios (user_id, code, date, name, time, calories, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $this->generateCode(), $date, $name, $time, $calories, $description]);

        return null;
    }
}
