<?php

namespace Brash\Dbal\Drivers\AsyncSqlite;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as DoctrineResult;

class Result implements DoctrineResult
{
    public function __construct(private ?\Clue\React\SQLite\Result $result)
    {
        $this->result = $result;
    }

    #[\Override]
    public function fetchNumeric(): array|false
    {
        $row = $this->fetchAssociative();
        if ($row === false) {
            return false;
        }

        return \array_values($row);
    }

    #[\Override]
    public function fetchAssociative(): array|false
    {
        return count($this->result->rows) > 0 ? array_pop($this->result->rows) : false;
    }

    #[\Override]
    public function fetchOne(): mixed
    {
        return FetchUtils::fetchOne($this);
    }

    #[\Override]
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    #[\Override]
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    #[\Override]
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    #[\Override]
    public function rowCount(): int
    {
        return $this->result->changed;
    }

    #[\Override]
    public function columnCount(): int
    {
        return \count($this->result->columns);
    }

    #[\Override]
    public function free(): void
    {
        $this->result = null;
    }
}
