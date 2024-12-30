<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver\Connection;


interface ConnectionPoolInterface
{
    public function extractConnection(array $params): Connection;

    /**
     * @return int Total number of active connections in the pool.
     */
    public function getConnectionsCount(): int;

    /**
     * @return int Total number of idle connections in the pool.
     */
    public function getIdleConnectionsCount(): int;

    /**
     * @return int Maximum number of connections this pool will create.
     */
    public function getConnectionLimit(): int;

    /**
     * @return int Number of seconds a connection may remain idle before it is automatically closed.
     */
    public function getIdleTimeout(): int;

    public function returnConnection(Connection $connection): void;
}
