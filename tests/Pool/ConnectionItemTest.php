<?php

declare(strict_types=1);

namespace Tests\Pool;

use Brash\Dbal\Pool\ConnectionItem;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use Doctrine\DBAL\Driver\Connection;

test('Should assert raw connection object is received', function(){
    $obj = new \stdClass();
    $obj->close = fn()=> null;
    $connMock = $this->createMock(Connection::class);
    $connMock->expects($this->once())
    ->method('getNativeConnection')
    ->willReturn($obj);
    $poolItem = new ConnectionItem($connMock, new ConnectionPoolOptions());

    expect($poolItem->reveal())->toBe($connMock);
    expect($poolItem->reveal()->getNativeConnection())->toBe($obj);
});

test('Should call correct native connection on close', function(){
    $nativeConnMock = mock();
    $nativeConnMock->shouldReceive('close')->once();
    $connMock = $this->createMock(Connection::class);
    $connMock->expects($this->once())
    ->method('getNativeConnection')
    ->willReturn($nativeConnMock);
    $poolItem = new ConnectionItem($connMock, new ConnectionPoolOptions());
    $poolItem->close();
});