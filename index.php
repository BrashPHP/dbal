<?php

use Brash\Dbal\DriverManager;

require_once __DIR__.'/vendor/autoload.php';

$connectionParams = [
    'dbname' => 'mydb',
    'user' => 'root',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'async_mysql',
    'port' => 3306,
];

$conn = DriverManager::getConnection($connectionParams);

$schema = new \Doctrine\DBAL\Schema\Schema;
$table = $schema->createTable('test');
$table->addColumn('id', 'integer', ['unsigned' => true]);
$table->setPrimaryKey(['id']);
$table->addColumn('username', 'string', ['length' => 32]);

$conn->executeStatement(implode(';', $schema->toSql($conn->getDatabasePlatform())));

$conn->insert('test', [
    'id' => 1,
    'username' => 'elgabo',
]);
