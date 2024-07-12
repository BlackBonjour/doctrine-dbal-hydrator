<?php

declare(strict_types=1);

namespace BlackBonjour\DbalHydrator;

use BlackBonjour\DbalHydrator\ResultSet\ResultSetInterface;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class Gateway
{
    public function __construct(
        private Connection $connection,
        private ResultSetInterface $resultSetPrototype,
    ) {
    }

    /**
     * @throws Exception
     */
    public function executeQuery(
        string $sql,
        array $param = [],
        array $types = [],
        ?QueryCacheProfile $qcp = null
    ): ResultSetInterface {
        $result = $this->connection->executeQuery($sql, $param, $types, $qcp);

        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        return $resultSet;
    }
}
