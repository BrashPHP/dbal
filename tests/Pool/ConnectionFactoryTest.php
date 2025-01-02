<?php

declare(strict_types=1);

namespace Tests\Pool;

use Brash\Dbal\Pool\ConnectionFactory;
use Brash\Dbal\Pool\ConnectionItem;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->mockDriver = $this->createMock(Driver::class);
    $this->mockLogger = $this->createMock(LoggerInterface::class);
    $this->connectionFactory = new ConnectionFactory(
        $this->mockDriver,
        $this->mockLogger
    );
});

it('creates a ConnectionItem on successful connection', function () {
    $mockConnection = $this->createMock(Connection::class);

    $this->mockDriver
        ->expects($this->once())
        ->method('connect')
        ->willReturn($mockConnection);

    $params = ['dbname' => 'test', 'user' => 'root', 'password' => 'password'];
    $connectionItem = $this->connectionFactory->create($params, new ConnectionPoolOptions);

    expect($connectionItem)->toBeInstanceOf(ConnectionItem::class);
});

it('returns null and logs an alert on connection failure', function () {
    $this->mockDriver
        ->expects($this->once())
        ->method('connect')
        ->will($this->throwException(new \Exception('Connection failed')));

    $this->mockLogger
        ->expects($this->once())
        ->method('alert')
        ->with(
            $this->equalTo('Connection failed'),
            $this->callback(function ($context) {
                return isset($context['database_error']) && isset($context['database_error_trace']);
            })
        );

    $params = ['dbname' => 'test', 'user' => 'root', 'password' => 'password'];
    $connectionItem = $this->connectionFactory->create($params, new ConnectionPoolOptions);

    expect($connectionItem)->toBeNull();
});
