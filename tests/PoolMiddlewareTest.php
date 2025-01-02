<?php

use Brash\Dbal\Observer\AcceptEmitterInterface;
use Brash\Dbal\Observer\CompletionEmitter;
use Brash\Dbal\Pool\ConnectionPoolInterface;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use Brash\Dbal\Pool\PooledDriverDecorator;
use Brash\Dbal\PoolMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;

it('can create itself using a driver and optional pool options', function () {
    $driver = mock(Driver::class);
    $connectionPoolOptions = new ConnectionPoolOptions;

    /** @var LoopInterface|\Mockery\MockInterface */
    $loopInterface = mock(LoopInterface::class);
    $timer = new Timer(0, fn () => null);
    $loopInterface->shouldReceive('cancelTimer')->withAnyArgs();

    $loopInterface->shouldReceive('addPeriodicTimer')->andReturn($timer);
    $middleware = PoolMiddleware::createSelf(
        $driver,
        $connectionPoolOptions,
        loopInterface: $loopInterface
    );

    expect($middleware)->toBeInstanceOf(PoolMiddleware::class);
});

it('returns a pooled driver decorator if the driver accepts emitters', function () {
    $completionEmitter = new CompletionEmitter;
    $poolInterface = mock(ConnectionPoolInterface::class);
    $driver = mock(AcceptEmitterInterface::class, Driver::class);
    $driver->shouldReceive('accept')->with($completionEmitter)->once();

    $middleware = new PoolMiddleware($completionEmitter, $poolInterface);

    $wrappedDriver = $middleware->wrap($driver);

    expect($wrappedDriver)->toBeInstanceOf(PooledDriverDecorator::class);
});

it('returns the original driver if it does not accept emitters', function () {
    $completionEmitter = new CompletionEmitter;
    $poolInterface = mock(ConnectionPoolInterface::class);
    $driver = mock(Driver::class);

    $middleware = new PoolMiddleware($completionEmitter, $poolInterface);

    $wrappedDriver = $middleware->wrap($driver);

    expect($wrappedDriver)->toBe($driver);
});

it('returns connections to the pool upon completion', function () {
    $poolInterface = mock(ConnectionPoolInterface::class);
    $completionEmitter = new CompletionEmitter;

    $middleware = new PoolMiddleware($completionEmitter, $poolInterface);

    $connection = mock(Connection::class);
    $poolInterface->shouldReceive('returnConnection')->with($connection)->once();

    $completionEmitter->notifyCompleted($connection);
});
