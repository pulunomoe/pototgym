<?php

namespace Com\Pulunomoe\PototGym\Model\Interface;

interface FindOneForUserInterface
{
    public function findOne(string $code, int $userId): array|bool;
}
