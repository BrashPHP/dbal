<?php

use Brash\Dbal\DriverManager;

require_once __DIR__."/vendor/autoload.php";

$connectionParams = [
    'dbname' => 'mydb',
    'user' => 'user',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'async_mysql',
];

$conn = DriverManager::getConnection($connectionParams);

$schema = new \Doctrine\DBAL\Schema\Schema();
$table = $schema->createTable("test");
$table->addColumn("id", "integer", ["unsigned" => true]);
$table->addColumn("username", "string", ["length" => 32]);

$queries = $schema->toSql($myPlatform); // get queries to create this schema.
$conn->executeStatement($schema->toSql($conn->getDatabasePlatform()));

$conn->insert("test", [
    'username' => "elgabo"
]);
