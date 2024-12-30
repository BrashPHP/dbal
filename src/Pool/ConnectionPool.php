<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Doctrine\DBAL\Driver\Connection;
use SplQueue;
use function React\Async\{await, async};
use function React\Promise\Timer\sleep as r_sleep;
use function React\Promise\{resolve, reject};

/**
 * @implements ConnectionPoolInterface<Connection>
 * @template-implements ConnectionPoolInterface<Connection>
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /** @var \SplQueue<ConnectionItem> */
    private SplQueue $idle;

    /** @var ?Deferred<null> */
    private readonly Deferred $onClose;
    private int $countPopAttempts;
    private bool $isClosed;

    /** @var \WeakMap<Connection,ConnectionItem> */
    private \WeakMap $connections;

    public function __construct(
        private readonly ConnectionFactory $factory,
        private ConnectionPoolOptions $options,
        private LoggerInterface $loggerInterface,
        private LoopInterface $loopInterface
    ) {
        $this->idle = new SplQueue();
        $this->connections = new \WeakMap();
        $this->onClose = new Deferred();
        $this->countPopAttempts = 0;
        $this->loggerInterface = $loggerInterface ?? new NullLogger();

        $timer = $this->loopInterface->addPeriodicTimer($options->discardIdleConnectionsIn, async(function () {
            $this->loggerInterface?->debug("Called discard connections");
            $now = \time();
            while (!$this->idle->isEmpty()) {
                /** @var PoolItem */
                $connection = $this->idle->current();

                if ($connection === null) {
                    $this->idle->pop();
                    continue;
                }

                if ($connection->getLastUsedAt() + $this->options->idleTimeout > $now) {
                    return;
                }

                // Close connection and remove it from the pool.
                /** @var ConnectionItem */
                $connection = $this->idle->dequeue();
                $connection->close();
                $this->connections->offsetUnset($connection->item);
            }
        }));

        $this->isClosed = false;
        $this->onClose
            ->promise()
            ->finally(fn() => $this->loopInterface->cancelTimer($timer));
    }

    public function size(): int
    {
        return $this->connections->count();
    }

    public function extractConnection(array $params): Connection
    {
        $poolItem = await($this->pop($params));

        return $poolItem->item;
    }

    public function getConnectionLimit(): int
    {
        return $this->getConnectionLimit();
    }

    public function getConnectionsCount(): int
    {
        return $this->size();
    }

    public function getIdleConnectionsCount(): int
    {
        return $this->idle->count();
    }
    public function getIdleTimeout(): int
    {
        return $this->options->idleTimeout;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getCountPopAttempts()
    {
        return $this->countPopAttempts;
    }

    public function getLastUsedAt(): int
    {
        $time = 0;

        foreach ($this->connections as $connection) {
            if ($connection->isLocked) {
                $lastUsedAt = $connection->getLastUsedAt();
                $time = max($time, $lastUsedAt);
            }
        }

        return $time;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * Close all connections in the pool. No further queries may be made after a pool is closed.
     *
     * Fatalistic scenario: kills every locked connections as well.
     */
    public function close(): void
    {
        if ($this->isClosed) {
            return;
        }

        foreach ($this->connections as $connection) {
            $this->loopInterface->futureTick(fn() => $connection->close());
        }

        $this->isClosed = true;

        $this->onClose->resolve(null);
    }

    public function returnConnection(Connection $connection): void
    {
        $this->loggerInterface?->debug("Returned connection");
        $poolItem = $this->connections->offsetGet($connection);

        $this->push($poolItem);
    }

    /**
     * @return PromiseInterface<PoolItem<Connection>>
     *
     * @throws ConnectionPoolException If no connections are available to be created
     * @throws \Error If the pool has been closed.
     */
    protected function pop(array $params): PromiseInterface
    {
        $this->loggerInterface?->debug("Attempting to get available connection");
        $poolUnavailableException = $this->checkAvailability();
        if ($poolUnavailableException === null) {
            return $this->getConnection($params);
        }

        return reject($poolUnavailableException);
    }

    private function getConnection(array $params): PromiseInterface
    {
        // Attempt to get an idle connection.
        while (!$this->idle->isEmpty()) {
            $connection = $this->idle->dequeue();

            if (!$connection->isClosed()) {
                $connection->lock();
                $this->resetAttemptsCounter();

                return resolve($connection);
            }
        }

        if ($this->size() < $this->options->maxConnections) {
            $connection = $this->createConnection($params);

            if (!is_null($connection)) {
                $this->loggerInterface->debug("Connection created.");

                $connection->lock();
                $this->resetAttemptsCounter();

                return resolve($connection);
            }
        }

        // Retry until an active connection is obtained or the pool is closed.
        return r_sleep(0.01, $this->loopInterface)->then(fn() => $this->pop($params));
    }

    private function checkAvailability(): \Exception|null
    {
        $maxRetries = $this->options->maxRetries;
        if (++$this->countPopAttempts >= $maxRetries) {
            $this->loggerInterface?->debug("Max attempts achieved");

            $this->close();

            return new ConnectionPoolException(
                "No available connection to use; $maxRetries retries were made and reached the limit"

            );
        }
        if ($this->isClosed()) {
            return new \RuntimeException("The pool has been closed");
        }

        return null;
    }

    /**
     *
     * @throws \Error If the connection is not part of this pool.
     */
    protected function push(PoolItem $connection): void
    {
        \assert(
            $this->connections->offsetExists($connection->item),
            "Connection is not part of this pool"
        );

        $connection->unlock();

        if (!$connection->isClosed()) {
            $this->idle->enqueue($connection);
        }
        else{
            $this->connections->offsetUnset($connection->item);
        }
    }

    /** @return PoolItem<Connection>|null */
    private function createConnection(array $params): ?PoolItem
    {
        if ($this->isClosed()) {
            return null;
        }

        $connection = $this->factory->create($params);

        if (is_null($connection)) {
            return null;
        }

        if ($connection->validate()) {
            $this->connections->offsetSet($connection->reveal(), $connection);

            return $connection;
        }

        throw new \Error("Invalid object created for " . get_class($connection) . PHP_EOL);
    }

    private function resetAttemptsCounter()
    {
        $this->countPopAttempts = 0;
    }
}
