<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

final class PoolFactory
{
    public function createPool(
        Driver $driver,
        ConnectionPoolOptions|null $connectionPoolOptions = null,
        LoggerInterface|null $logger = null,
        LoopInterface|null $loop = null
    ): ConnectionPoolInterface {
        $connectionPoolOptions ??= new ConnectionPoolOptions();
        $logger ??= new NullLogger();
        $loop ??= Loop::get();

        return new ConnectionPool(
            factory: new ConnectionFactory($driver),
            loopInterface: $loop,
            loggerInterface: $logger,
            options: $connectionPoolOptions
        );
    }
}
