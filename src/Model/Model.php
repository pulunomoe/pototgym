<?php

namespace Com\Pulunomoe\PototGym\Model;

use PDO;
use PDOStatement;
use Ramsey\Uuid\Uuid;

abstract class Model
{
    public function __construct(protected PDO $pdo)
    {
    }

    protected function prepare(string $stmt): PDOStatement
    {
        return $this->pdo->prepare($stmt);
    }

    protected function generateCode(): string
    {
        return gmp_strval(gmp_init(str_replace('-', '', Uuid::uuid4()->toString()), 16), 62);
    }
}
