<?php

namespace Tests\Connection\Support;

use Doctrine\DBAL\Connection;

class InfrastructureSupport
{
    public function __construct(private string $tableName = 'table_test') {}

    public function createInfrastructure(
        Connection $conn,
    ) {
        $schema = new \Doctrine\DBAL\Schema\Schema;
        $tableName = $this->tableName;
        $sm = $conn->createSchemaManager();
        if (! $schema->hasTable($tableName) && ! in_array($tableName, $sm->listTableNames())) {
            $table = $schema->createTable($tableName);
            $table->addColumn('id', 'integer', ['unsigned' => true])->setAutoincrement(true);
            $table->setPrimaryKey(['id']);
            $table->addColumn('field1', 'string', ['length' => 32]);
            $table->addColumn('field2', 'string', ['length' => 32]);
            $sm->createTable($table);
        }
    }

    public function dropInfrastructure(Connection $conn)
    {
        $tableName = $this->tableName;
        $sm = $conn->createSchemaManager();
        if (in_array($tableName, $sm->listTableNames(), true)) {
            $sm->dropTable($tableName);
        }
    }

    public function resetInfrastructure(Connection $conn)
    {
        try {
            $schema = new \Doctrine\DBAL\Schema\Schema;
            $tableName = $this->tableName;
            $sm = $conn->createSchemaManager();
            if (! $schema->hasTable($tableName) && ! in_array($tableName, $sm->listTableNames())) {
                $table = $schema->createTable($tableName);
                $table->addColumn('id', 'integer', ['unsigned' => true])->setAutoincrement(true);
                $table->setPrimaryKey(['id']);
                $table->addColumn('field1', 'string', ['length' => 32]);
                $table->addColumn('field2', 'string', ['length' => 32]);
                $sm->createTable($table);
            } else {
                $sm->dropTable($tableName);
            }
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}
