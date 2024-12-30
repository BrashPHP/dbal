<?php

namespace Brash\Dbal\Drivers\AsyncMysql;

use Doctrine\DBAL\Driver\AbstractException;

final class MysqlException extends AbstractException
{
    public static function new(\Throwable $exception): self
    {
        return new self($exception->getMessage(), null, $exception->getCode(), $exception);
    }
}
