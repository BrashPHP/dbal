<?php

use Brash\Dbal\DriverManager;

require_once __DIR__.'/../vendor/autoload.php';

$connectionParams = [
    'dbname' => 'mydb.db',
    'user' => 'root',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'async_sqlite',
    'port' => 3306,
];

$conn = DriverManager::getConnection($connectionParams);

$schema = new \Doctrine\DBAL\Schema\Schema;
$tableName = 'test';
$sm = $conn->createSchemaManager();
if (! $schema->hasTable($tableName) && ! in_array($tableName, $sm->listTableNames())) {
    $table = $schema->createTable($tableName);
    $table->addColumn('id', 'integer', ['unsigned' => true])->setAutoincrement(true);
    $table->setPrimaryKey(['id']);
    $table->addColumn('username', 'string', ['length' => 32]);

    $conn->executeStatement(implode(';', $schema->toSql($conn->getDatabasePlatform())));
}

$conn->insert($tableName, [
    'username' => 'elgabo',
]);
