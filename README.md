<!-- <p align="center">
    <img src="https://raw.githubusercontent.com/nunomaduro/skeleton-php/master/docs/example.png" height="300" alt="Skeleton Php">
    <p align="center">
        <a href="https://github.com/nunomaduro/skeleton-php/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/nunomaduro/skeleton-php/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/nunomaduro/skeleton-php"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/nunomaduro/skeleton-php"></a>
        <a href="https://packagist.org/packages/nunomaduro/skeleton-php"><img alt="Latest Version" src="https://img.shields.io/packagist/v/nunomaduro/skeleton-php"></a>
        <a href="https://packagist.org/packages/nunomaduro/skeleton-php"><img alt="License" src="https://img.shields.io/packagist/l/nunomaduro/skeleton-php"></a>
    </p>
</p> -->

# Brash DBAL

---

This is a DBAL on top of ReactPHP SQL clients and Doctrine DBAL. With this, you will be able to use

    Doctrine QueryBuilder model
    Doctrine Schema model
    SQL Statements
    Easy-to-use shortcuts for common operations

Using this non-blocking approach, you release your processes from the burden of keeping await operations while you can take the most out of your processor, with the advantage of seemingly unnoticeable `await` keywords.

## How to use

You may find concrete examples (and working!) in `examples` directory, but this package does pretty much what Doctrine DBAL does, since it is just a way to extend Doctrine DBAL's already useful and knwon qualities. The additions are transparent, but basically:

-   A connection pool
-   Async drivers
-   A custom Driver Manager

It goes like this ;)

```php

use Brash\Dbal\DriverManager;

$connectionParams = [
    'dbname' => 'mydb',
    'user' => 'root',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'async_mysql', # pay attention to the driver selection!
    'port' => 3306
];

$conn = DriverManager::getConnection($connectionParams);
$conn->insert("test", [
    'id' => 1,
    'username' => "Gabo Bertir"
]);

```

## Configuration

You can configure the driver manager directly from the class just like the connection pool values.

```php

use Brash\Dbal\DriverManager;

DriverManager::setPoolOptions(new ConnectionPoolOptions(
    maxConnections: 10,
    idleTimeout: 2, # in seconds
    maxRetries: 5,
    discardIdleConnectionsIn: 5, # seconds
    minConnections: 2,
    keepAliveIntervalSec: 0 # Disabled when 0
));

DriverManager::getConnection([...]);

```

## Driver Options

Choose between MySQL, MariaDB, Postgres and SQLite, just passing the correct driver in your `Connection Params`.

```php
    'async_postgres' => AsyncPostgresDriver::class,
    'async_mysql' => AsyncMysqlDriver::class,
    'async_sqlite' => AsyncSqliteDriver::class,
```

## Under the hood

Internally, the:
Postgres Async Driver is an implementation on top of Voryx/PgAsync;
SQLite Async Driver is an implementation on top of Clue/reactphp-sqlite, and
MySQL/MariaDB is an implementation on top of react/mysql.

> **Requires [PHP 8.3+](https://php.net/releases/)**

🧹 Keep a modern codebase with **Pint**:

```bash
composer lint
```

✅ Run refactors using **Rector**

```bash
composer refacto
```

⚗️ Run static analysis using **PHPStan**:

```bash
composer test:types
```

✅ Run unit tests using **PEST**

```bash
composer test:unit
```

🚀 Run the entire test suite:

```bash
composer test
```
