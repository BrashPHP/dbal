<?php

declare(strict_types=1);

namespace Brash\Dbal;

use Brash\Dbal\Pool\ConnectionPoolInterface;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use Brash\Dbal\Pool\PooledDriverDecorator;
use Brash\Dbal\Observer\AcceptEmitterInterface;
use Brash\Dbal\Observer\CompletionEmitter;
use Brash\Dbal\Observer\CompletionObserverInterface;
use Brash\Dbal\Pool\PoolFactory;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver;

final readonly class AsyncMiddleware implements Middleware
{
    public function __construct(private CompletionEmitter $completionEmitter, private ConnectionPoolInterface $poolInterface)
    {
        $completionEmitter->includeObserver(new class ($poolInterface) implements CompletionObserverInterface {
            public function __construct(private readonly ConnectionPoolInterface $connectionPoolInterface)
            {
            }
            public function update(Connection $connection): void
            {
                $this->connectionPoolInterface->returnConnection($connection);
            }
        });
    }

    public static function createSelf(Driver $driver, ?ConnectionPoolOptions $connectionPoolOptions = null): self{
        $connectionPool = (new PoolFactory())->createPool($driver, $connectionPoolOptions);
        return new self(
            new CompletionEmitter(),
            $connectionPool
        );
    }

    public function wrap(Driver $driver): Driver
    {
        if ($driver instanceof AcceptEmitterInterface) {
            $driver->accept($this->completionEmitter);

            return new PooledDriverDecorator($driver, $this->poolInterface);
        }

        return $driver;
    }
}
