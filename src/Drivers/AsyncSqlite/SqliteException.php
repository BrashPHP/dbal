<?php

namespace Brash\Dbal\Drivers\AsyncSqlite;

use Doctrine\DBAL\Driver\AbstractException;

final class SqliteException extends AbstractException
{
    public static function new(\Throwable $exception): self
    {
        return new self(
            $exception->getMessage(),
            null,
            $exception->getCode(),
            $exception
        );
    }
}
