<?php

use Brash\Dbal\DriverManager;

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

$result = $conn
    ->createQueryBuilder()
    ->select('*')
    ->from('test', 'u')
    ->where('u.id = :id')
    ->setParameter('id', 1)
    ->setMaxResults(1)
    ->executeQuery();

dd($result->fetchAllAssociative());
