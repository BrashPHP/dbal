<?php

namespace Brash\Dbal\Observer;

final readonly class SqlResult
{
    public function __construct(
        public int|null $insertId,
        public int|null $affectedRows,
        public array|null $resultFields,
        public array|null $resultRows,
        public int|null $warningCount
    ) {
    }
}
