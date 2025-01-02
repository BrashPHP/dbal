<?php

use Brash\Dbal\DriverManager;
use Brash\Dbal\Pool\ConnectionPoolOptions;
use React\EventLoop\Loop;
use React\Promise\Promise;

require_once __DIR__.'/../vendor/autoload.php';

$connectionParams = [
    'dbname' => 'mydb',
    'user' => 'root',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'async_mysql',
    'port' => 3306,
];

$conn = DriverManager::getConnection($connectionParams);
$conn2 = DriverManager::getConnection($connectionParams);

DriverManager::setPoolOptions(new ConnectionPoolOptions(
    maxConnections: 10,
    idleTimeout: 2, // in seconds
    maxRetries: 5,
    discardIdleConnectionsIn: 5, // seconds
    minConnections: 2,
));

React\Async\parallel([
    function () use ($conn) {
        return new Promise(function ($resolve) use ($conn) {

            $resolve($conn->executeQuery('select * from test'));
        });
    },
    function () use ($conn2) {
        return new Promise(function ($resolve) use ($conn2) {
            $resolve($conn2->executeQuery('select * from test'));

        });
    },
    function () {
        return new Promise(function ($resolve) {
            Loop::addTimer(1, function () use ($resolve) {
                $resolve('Slept for yet another whole second');
            });
        });
    },
])->then(function (array $results) {
    foreach ($results as $result) {
        var_dump($result);
    }
}, function (Exception $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
});

dump($conn === $conn2);
