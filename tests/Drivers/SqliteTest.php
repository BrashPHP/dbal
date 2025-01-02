<?php

declare(strict_types=1);

namespace Tests\Drivers;

use Brash\Dbal\DriverManager;
use Brash\Dbal\Drivers\AsyncSqlite\Driver;
use Brash\Dbal\Pool\PooledDriverDecorator;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;

test('should return mysql connection instance', function () {
    /** @var \Mockery\MockInterface|LoopInterface */
    $spyLoop = spy(LoopInterface::class);
    $timer = new Timer(0, fn () => null);
    $spyLoop->shouldReceive('addPeriodicTimer')->withAnyArgs()->andReturn($timer);
    $spyLoop->shouldReceive('addTimer')->withAnyArgs()->andReturn($timer);
    $connectionParams = [
        'dbname' => ':memory:',
        'driver' => 'async_sqlite',
    ];
    DriverManager::setLoop($spyLoop);
    $connection = DriverManager::getConnection($connectionParams);
    /** @var PooledDriverDecorator */
    $driver = $connection->getDriver();

    expect($driver)->toBeInstanceOf(PooledDriverDecorator::class);
    expect($driver->getDecorated())->toBeInstanceOf(Driver::class);
});
