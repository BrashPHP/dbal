<?php

namespace Tests\Connection;

final class ConnectionParams
{
    public static function sqliteParams(): array
    {
        return [
            'dbname' => ':memory:',
            'driver' => 'async_sqlite',
        ];
    }

    public static function mysqlParams(): array
    {
        return [
            'dbname' => 'mydb',
            'user' => 'root',
            'password' => 'secret',
            'host' => 'localhost',
            'driver' => 'async_mysql',
            'port' => 3306,
        ];
    }

    public static function postgresParams(): array
    {
        return [
            'dbname' => 'mydb',
            'user' => 'root',
            'password' => 'secret',
            'host' => 'localhost',
            'driver' => 'async_postgres',
            'port' => 5432,
        ];
    }
}
