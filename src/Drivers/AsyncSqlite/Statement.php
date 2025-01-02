<?php

namespace Brash\Dbal\Drivers\AsyncSqlite;

use Brash\Dbal\Drivers\Exceptions\UnknownParameter;
use Brash\Dbal\Observer\ResultListenerInterface;
use Brash\Dbal\Observer\SqlResult;
use Clue\React\SQLite\DatabaseInterface;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;
use Doctrine\DBAL\ParameterType;

use function React\Async\await;

class Statement implements DoctrineStatement
{
    private const array PARAM_TYPES = [
        ParameterType::NULL,
        ParameterType::INTEGER,
        ParameterType::STRING,
        ParameterType::ASCII,
        ParameterType::BINARY,
        ParameterType::LARGE_OBJECT,
        ParameterType::BOOLEAN,
    ];

    private array $values = [];

    private array $types = [];

    public function __construct(
        private readonly DatabaseInterface $connection,
        private readonly string $sql,
        private readonly ResultListenerInterface $resultListener
    ) {}

    #[\Override]
    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        if (! in_array($type, self::PARAM_TYPES)) {
            throw UnknownParameter::new($type->name);
        }

        $key = \is_int($param) ? $param - 1 : $param;

        $this->values[$key] = $this->convertValue($value, $type);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): bool
    {
        if (! in_array($type, self::PARAM_TYPES)) {
            throw UnknownParameter::new($type->name);
        }

        $key = \is_int($param) ? $param - 1 : $param;

        $this->values[$key] = &$variable;
        $this->types[$key] = $type;

        return true;
    }

    #[\Override]
    public function execute($params = null): Result
    {
        $values = $this->values;

        if ($params !== null) {
            foreach ($params as $param) {
                $values[] = $param;
            }
        }

        // Convert references to correct types
        foreach ($this->types as $param => $type) {
            $values[$param] = $this->convertValue($values[$param], $type);
        }

        try {
            $promisedResult = $this->connection->query($this->sql, $values);
            $result = await($promisedResult);

            $this->resultListener->listen(new SqlResult(
                $result->insertId,
                $result->changed,
                $result->columns,
                $result->rows,
                null
            ));

            return new Result($result);
        } catch (\Throwable $e) {
            throw SqliteException::new($e);
        }
    }

    private function convertValue($value, ParameterType $type): null|bool|int|string
    {
        return match ($type) {
            ParameterType::NULL => null,
            ParameterType::INTEGER => (int) $value,
            ParameterType::ASCII, ParameterType::LARGE_OBJECT, ParameterType::BINARY, ParameterType::STRING => (string) $value,
            ParameterType::BOOLEAN => (bool) $value,
            default => throw new UnknownParameter($type->name),
        };
    }
}
