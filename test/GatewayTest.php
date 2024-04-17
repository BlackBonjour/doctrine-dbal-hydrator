<?php

declare(strict_types=1);

namespace BlackBonjourTest\DbalHydrator;

use BlackBonjour\DbalHydrator\Gateway;
use BlackBonjour\DbalHydrator\ResultSet\ResultSetInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Throwable;

class GatewayTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testExecuteQuery(): void
    {
        $result = $this->createMock(Result::class);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('executeQuery')
            ->with('SELECT * FROM `user`')
            ->willReturn($result);

        $resultSetPrototype = $this->createMock(ResultSetInterface::class);
        $resultSetPrototype
            ->expects(self::once())
            ->method('initialize')
            ->with($result);

        $gateway = new Gateway($connection, $resultSetPrototype);

        self::assertEquals($resultSetPrototype, $gateway->executeQuery('SELECT * FROM `user`'));
    }
}
