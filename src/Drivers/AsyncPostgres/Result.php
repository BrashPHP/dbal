<?php

namespace Brash\Dbal\Drivers\AsyncPostgres;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as DoctrineResult;

class Result implements DoctrineResult
{
    public function __construct(private mixed $rows) {}

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
        return count($this->rows) > 0 ? array_pop($this->rows) : false;
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
        return \count($this->rows ?? 0);
    }

    #[\Override]
    public function columnCount(): int
    {
        if (is_array($this->rows)) {
            $key = array_key_first($this->rows);

            return count($this->rows[$key]);
        }

        return 1;
    }

    #[\Override]
    public function free(): void
    {
        $this->result = null;
    }
}
