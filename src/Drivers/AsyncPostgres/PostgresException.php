<?php

namespace Brash\Dbal\Drivers\AsyncPostgres;

use Doctrine\DBAL\Driver\AbstractException;

final class PostgresException extends AbstractException
{
    public static function new(\Throwable $exception): self
    {
        return new self($exception->getMessage(), null, $exception->getCode(), $exception);
    }
}
