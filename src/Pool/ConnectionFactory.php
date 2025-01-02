<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver;
use Psr\Log\LoggerInterface;

class ConnectionFactory
{
    public function __construct(
        private Driver $driver,
        private LoggerInterface $loggerInterface
    ) {
    }

    public function create(array $params, ConnectionPoolOptions $connectionPoolOptions): ?ConnectionItem
    {
        try {
            $conn = $this->driver->connect($params);

            return new ConnectionItem($conn, $connectionPoolOptions);
        } catch (\Throwable $th) {
            $this->loggerInterface->alert($th->getMessage(), [
                'database_error' => $th,
                'database_error_trace' => $th->getTrace()
            ]);
        
            return null;
        }
    }
}
