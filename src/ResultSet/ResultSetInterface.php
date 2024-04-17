<?php

declare(strict_types=1);

namespace BlackBonjour\DbalHydrator\ResultSet;

use Closure;
use Countable;
use Doctrine\DBAL\Result;
use Iterator;

interface ResultSetInterface extends Countable, Iterator
{
    public function getResult(): Result;

    public function initialize(Result $result): void;

    public function toArray(Closure|string $withKey = null): array;
}
