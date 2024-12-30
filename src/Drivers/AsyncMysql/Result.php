<?php

namespace Brash\Dbal\Drivers\AsyncMysql;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as DoctrineResult;
use React\MySQL\QueryResult;

class Result implements DoctrineResult
{
    public function __construct(private ?QueryResult $result)
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
        return $this->result->resultRows ?? false;
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
        return \count($this->result->resultRows);
    }

    public function columnCount(): int
    {
        return \count($this->result->resultFields);
    }

    public function free(): void
    {
        if (!$this->result instanceof \React\MySQL\QueryResult) {
            return;
        }

        $this->result = null;
    }
}