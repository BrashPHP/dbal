<?php

namespace Brash\Dbal\Observer;

final readonly class SqlResult
{
    public function __construct(
        public ?int $insertId,
        public ?int $affectedRows,
        public ?array $resultFields,
        public ?array $resultRows,
        public ?int $warningCount
    ) {}
}
