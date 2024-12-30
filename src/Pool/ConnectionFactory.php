<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver\Connection;

class ConnectionFactory
{
    public function __construct(private \Doctrine\DBAL\Driver $driver)
    {
    }
    /**
     *
     * @return ?PoolItem<Connection>
     */
    public function create(array $params): ?PoolItem
    {
        $conn = $this->driver->connect($params);

        return new ConnectionItem($conn);
    }
}
