<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;

class PooledDriverDecorator implements Driver
{
    public function __construct(
        private readonly Driver $decorated,
        private readonly ConnectionPoolInterface $connectionPoolInterface
    ) {}

    public function connect(array $params): Connection
    {
        return $this->connectionPoolInterface->extractConnection($params);
    }

    public function getDatabasePlatform(\Doctrine\DBAL\ServerVersionProvider $versionProvider): \Doctrine\DBAL\Platforms\AbstractPlatform
    {
        return $this->decorated->getDatabasePlatform($versionProvider);
    }

    public function getExceptionConverter(): Driver\API\ExceptionConverter
    {
        return $this->decorated->getExceptionConverter();
    }
}
