<?php

declare(strict_types=1);

namespace Tests\Pool;

use Brash\Dbal\Pool\ConnectionFactory;
use Brash\Dbal\Pool\ConnectionPool;
use Brash\Dbal\Pool\ConnectionItem;
use Brash\Dbal\Pool\ConnectionPoolException;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use Brash\Dbal\Pool\PoolItem;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Doctrine\DBAL\Driver\Connection;
use React\EventLoop\Timer\Timer;


function generateInput(): ConnectionItem
{
    /** @var Connection|MockInterface */
    $mockConnection = spy(Connection::class);
    $mockConnection->shouldReceive('getNativeConnection')->withAnyArgs();
    return new ConnectionItem($mockConnection, new ConnectionPoolOptions());
}

function createFactory(): ConnectionFactory|MockInterface
{
    /**
     * @var ConnectionFactory|\Mockery\MockInterface
     */
    $connectionFactory = mock(ConnectionFactory::class);
    $items = array_map(fn() => generateInput(), range(1, 25));
    $connectionFactory->shouldReceive('create')->andReturn(array_shift($items), ...$items);

    return $connectionFactory;
}

function createSut(ConnectionFactory $connectionFactory = null): ConnectionPool
{
    $connectionFactory ??= createFactory();
    $loggerInterface = new NullLogger();
    /** @var \Mockery\MockInterface|LoopInterface */
    $loopInterface = mock(LoopInterface::class);

    $timer = new Timer(0, fn()=>null);
    $loopInterface->shouldReceive('addPeriodicTimer')->withAnyArgs()->andReturn($timer);
    $loopInterface->shouldReceive('addTimer')->withAnyArgs()->andReturn($timer);
    $loopInterface->shouldReceive('futureTick')->withAnyArgs();
    $loopInterface->shouldReceive('cancelTimer')->withAnyArgs();

    $connectionOptions = new ConnectionPoolOptions();

    return new ConnectionPool(
        $connectionFactory,
        $connectionOptions,
        $loggerInterface,
        $loopInterface
    );
}

beforeEach(function () {
    $this->sut = createSut();
});

afterEach(function () {
    $this->sut->close();
});


it('should receive valid connection', function () {
    $poolItem = $this->sut->extractConnection([]);

    expect($poolItem)->toBeInstanceOf(Connection::class);
});

it('should exhaust get connection and throw', function () {
    /** @var ConnectionFactory|\Mockery\MockInterface */
    $factoryMock = mock(ConnectionFactory::class);
    $factoryMock->shouldReceive('create')->andReturn(null);
    $sut = new ConnectionPool(
        $factoryMock,
        new ConnectionPoolOptions(),
        new NullLogger(),
        Loop::get()
    );

    $sut->extractConnection([]);
    $sut->close();
})->throws(ConnectionPoolException::class);

it('should create only max number of connections', function () {
    /** @var \Mockery\MockInterface|ConnectionFactory */
    $connectionFactory = mock(ConnectionFactory::class);
    $items = array_map(fn($el) => generateInput(), range(1, 115));
    $connectionFactory->shouldReceive('create')->andReturn(array_shift($items), ...$items);
    $sut = createSut($connectionFactory);
    $maxConnections = $sut->getConnectionLimit();

    $poolItems = [];
    for ($i = 0; $i < 115; $i++) {
        $item = $sut->extractConnection([]);
        $poolItems[] = $item;
        if ($i % $maxConnections === 0) {
            foreach ($poolItems as $item) {
                $sut->returnConnection($item);
            }
        }
    }

    $sut->close();
    expect($sut->size())->toBe($maxConnections);
});

it('should always return idle connection', function () {
    $sut = $this->sut;
    $poolItems = [];
    for ($i = 0; $i < 20; $i++) {
        $item = $sut->extractConnection([]);
        $poolItems[] = $item;
        $sut->returnConnection($poolItems[$i]);
    }

    expect($sut->size())->toBe(1);
    expect($poolItems)->toBeArray();
});

it('should reset get counter', function () {
    /** @var \Mockery\MockInterface|ConnectionFactory */
    $defaultFactory = mock(ConnectionFactory::class);
    $defaultFactory->shouldReceive('create')->andReturnValues([null, null, generateInput()]);
    
    $sut = new ConnectionPool(
        $defaultFactory,
        new ConnectionPoolOptions(),
        new NullLogger(),
        Loop::get()
    );

    $sut->extractConnection([]);

    $sut->close();

    expect($sut->getCountPopAttempts())->toBe(0);
});
