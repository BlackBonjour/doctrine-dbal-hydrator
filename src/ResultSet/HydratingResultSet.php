<?php

declare(strict_types=1);

namespace BlackBonjour\DbalHydrator\ResultSet;

use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Laminas\Hydrator\HydrationInterface;

class HydratingResultSet implements ResultSetInterface
{
    private Result $result;

    private array $data;
    private array $objects;

    public function __construct(
        private HydrationInterface $hydration,
        private object $objectPrototype,
    ) {
    }

    /**
     * @throws Exception
     */
    public function count(): int
    {
        return $this->result->rowCount();
    }

    public function current(): ?object
    {
        $current = current($this->objects);

        return $current === false ? null : $current;
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @throws ResultSetException
     */
    public function initialize(Result $result): void
    {
        if (isset($this->result)) {
            throw new ResultSetException('Result set already initialized!');
        }

        try {
            $data = $result->fetchAllAssociative();
        } catch (Exception $e) {
            throw new ResultSetException(
                message : 'Failed to fetch associative data from result!',
                previous: $e,
            );
        }

        $this->data   = $data;
        $this->result = $result;

        $this->objects = array_map(
            fn (array $row): object => $this->hydration->hydrate($row, clone $this->objectPrototype),
            $data
        );
    }

    public function key(): ?int
    {
        return key($this->objects);
    }

    public function next(): void
    {
        next($this->objects);
    }

    public function rewind(): void
    {
        reset($this->objects);
    }

    /**
     * @return object[]
     * @throws ResultSetException
     */
    public function toArray(Closure|string $withKey = null): array
    {
        $array = [];

        foreach ($this as $index => $object) {
            if ($withKey instanceof Closure) {
                $key = ($withKey)($object);
            } elseif (is_string($withKey)) {
                if (array_key_exists($withKey, $this->data[$index]) === false) {
                    throw new ResultSetException(sprintf('Result set key "%s" does not exist!', $withKey));
                }

                $key = $this->data[$index][$withKey];
            } else {
                $key = $index;
            }

            if ((string) $key === '') {
                throw new ResultSetException(sprintf('Result set key "%s" is empty!', $withKey));
            }

            $array[$key] = $object;
        }

        return $array;
    }

    public function valid(): bool
    {
        return $this->current() !== null;
    }
}
