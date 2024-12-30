<?php

namespace Brash\Dbal\Drivers\AsyncMysql;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as DoctrineResult;
use React\MySQL\MysqlResult;

class Result implements DoctrineResult
{
    public function __construct(private ?MysqlResult $result)
    {
        $this->result = $result;
    }

    public function fetchNumeric(): array|false
    {
        $row = $this->fetchAssociative();
        if ($row === false) {
            return false;
        }

        return \array_values($row);
    }

    public function fetchAssociative(): array|false
    {
        return count($this->result->resultRows) > 0 ? array_pop($this->result->resultRows) : false;
    }

    public function fetchOne(): mixed
    {
        return FetchUtils::fetchOne($this);
    }

    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        return \count($this->result->resultRows ?? 0);
    }

    public function columnCount(): int
    {
        return \count($this->result->resultFields);
    }

    public function free(): void
    {
        $this->result = null;
    }
}