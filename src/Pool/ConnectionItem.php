<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Brash\Dbal\AsyncConnectionInterface;
use Doctrine\DBAL\Driver\Connection;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;

/**
 * @template-extends PoolItem<AsyncConnectionInterface>
 *
 * @extends parent<AsyncConnectionInterface>
 */
final class ConnectionItem extends PoolItem
{
    private ?TimerInterface $keepAliveTimer = null;

    public function __construct(
        AsyncConnectionInterface $connection,
        ConnectionPoolOptions $connectionPoolOptions
    ) {
        parent::__construct($connection);
        if ($connectionPoolOptions->keepAliveIntervalSec > 0) {
            $this->keepAliveTimer = Loop::get()->addPeriodicTimer(
                $connectionPoolOptions->keepAliveIntervalSec,
                function () use ($connection) {
                    $connection->query('SELECT 1');
                }
            );
        }
    }

    protected function onClose(): void
    {
        if ($this->keepAliveTimer !== null) {
            Loop::get()->cancelTimer($this->keepAliveTimer);
            $this->keepAliveTimer = null;
        }
        $this->item->close();
    }

    public function validate(): bool
    {
        return $this->item instanceof Connection;
    }
}
