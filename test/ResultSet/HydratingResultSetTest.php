<?php

declare(strict_types=1);

namespace BlackBonjourTest\DbalHydrator\ResultSet;

use ArrayObject;
use BlackBonjour\DbalHydrator\ResultSet\HydratingResultSet;
use BlackBonjour\DbalHydrator\ResultSet\ResultSetException;
use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Laminas\Hydrator\HydrationInterface;
use Laminas\Hydrator\HydratorInterface;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

class HydratingResultSetTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testCount(): void
    {
        $result = $this->mockResult(data: [], count: 123);

        $resultSet = new HydratingResultSet($this->createMock(HydratorInterface::class), new stdClass());
        $resultSet->initialize($result);

        self::assertEquals(123, $resultSet->count());
    }

    /**
     * @throws Throwable
     */
    public function testGetResult(): void
    {
        $result = $this->mockResult(data: []);

        $resultSet = new HydratingResultSet($this->createMock(HydratorInterface::class), new stdClass());
        $resultSet->initialize($result);

        self::assertEquals($result, $resultSet->getResult());
    }

    /**
     * @throws Throwable
     */
    public function testInitialize(): void
    {
        $hydration = $this->createMock(HydrationInterface::class);
        $hydration
            ->expects(self::exactly(2))
            ->method('hydrate')
            ->willReturnCallback(
                static function (array $data): object {
                    if (
                        $data === ['id' => 123, 'name' => 'Foo']
                        || $data === ['id' => 456, 'name' => 'Bar']
                    ) {
                        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
                    }

                    throw new LogicException(
                        sprintf(
                            'Data with ID "%s" and name "%s" is unexpected!',
                            $data['id'] ?? 'n/a',
                            $data['name'] ?? 'n/a'
                        )
                    );
                }
            );

        $result = $this->mockResult(
            data: [
                ['id' => 123, 'name' => 'Foo'],
                ['id' => 456, 'name' => 'Bar'],
            ]
        );

        $resultSet = new HydratingResultSet($hydration, new ArrayObject());
        $resultSet->initialize($result);
    }

    /**
     * @throws Throwable
     */
    public function testResultSetAlreadyInitialized(): void
    {
        $this->expectException(ResultSetException::class);
        $this->expectExceptionMessage('Result set already initialized!');

        $hydration = $this->createMock(HydrationInterface::class);
        $result    = $this->mockResult(data: []);

        $resultSet = new HydratingResultSet($hydration, new stdClass());
        $resultSet->initialize($result);
        $resultSet->initialize($result);
    }

    /**
     * @throws Throwable
     */
    public function testResultThrowingException(): void
    {
        $this->expectException(ResultSetException::class);
        $this->expectExceptionMessage('Failed to fetch associative data from result!');

        $hydration = $this->createMock(HydrationInterface::class);

        $result = $this->createMock(Result::class);
        $result
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException($this->createMock(Exception::class));

        $resultSet = new HydratingResultSet($hydration, new stdClass());
        $resultSet->initialize($result);
    }

    /**
     * Does also test implementation of Iterator interface.
     *
     * @throws Throwable
     */
    public function testToArray(): void
    {
        // Mock dependencies
        $hydration = $this->createMock(HydrationInterface::class);
        $hydration
            ->expects(self::exactly(2))
            ->method('hydrate')
            ->willReturnCallback(
                static fn (array $data): ArrayObject => new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS)
            );

        $result = $this->mockResult(
            data: [
                ['id' => 123, 'name' => 'Foo'],
                ['id' => 456, 'name' => 'Bar'],
            ]
        );

        // Create and initialize result set
        $resultSet = new HydratingResultSet($hydration, new ArrayObject());
        $resultSet->initialize($result);

        // Test case 1 - without specific key
        self::assertEquals(
            [
                0 => new ArrayObject(['id' => 123, 'name' => 'Foo']),
                1 => new ArrayObject(['id' => 456, 'name' => 'Bar']),
            ],
            $resultSet->toArray()
        );

        // Test case 2 - with ID as key by column name
        self::assertEquals(
            [
                123 => new ArrayObject(['id' => 123, 'name' => 'Foo']),
                456 => new ArrayObject(['id' => 456, 'name' => 'Bar']),
            ],
            $resultSet->toArray('id')
        );

        // Test case 3 - with ID as key by callback
        self::assertEquals(
            [
                123 => new ArrayObject(['id' => 123, 'name' => 'Foo']),
                456 => new ArrayObject(['id' => 456, 'name' => 'Bar']),
            ],
            $resultSet->toArray(static fn (ArrayObject $object): int => $object->id)
        );

        // Test case 4 - with name as key by callback
        self::assertEquals(
            [
                'Foo' => new ArrayObject(['id' => 123, 'name' => 'Foo']),
                'Bar' => new ArrayObject(['id' => 456, 'name' => 'Bar']),
            ],
            $resultSet->toArray(static fn (ArrayObject $object): string => $object->name)
        );
    }

    /**
     * @throws Throwable
     */
    private function mockResult(?array $data = null, ?int $count = null): Result&MockObject
    {
        $result = $this->createMock(Result::class);

        if ($data === null) {
            $result
                ->expects(self::never())
                ->method('fetchAllAssociative');
        } else {
            $result
                ->expects(self::once())
                ->method('fetchAllAssociative')
                ->willReturn($data);
        }

        if ($count === null) {
            $result
                ->expects(self::never())
                ->method('rowCount');
        } else {
            $result
                ->expects(self::once())
                ->method('rowCount')
                ->willReturn($count);
        }

        return $result;
    }
}
