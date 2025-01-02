<?php

namespace Brash\Dbal\Drivers\AsyncPostgres;

use Brash\Dbal\Observer\ResultListenerInterface;
use Brash\Dbal\Observer\SqlResult;
use Doctrine\DBAL\Driver\PgSQL\Exception\UnknownParameter;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;
use Doctrine\DBAL\ParameterType;
use PgAsync\ErrorException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

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
        private readonly \PgAsync\Connection $connection,
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
            $promisedResult = $this->execStatementOnSubscription($this->sql, $values);
            $result = await($promisedResult);

            $this->resultListener->listen(new SqlResult(
                null,
                $result->rowCount(),
                null,
                $result->fetchAllAssociative(),
                null
            ));

            return new Result($result);
        } catch (\Throwable $e) {
            throw PostgresException::new($e);
        }
    }

    private function fixSqlPreparedStatement(string $sql): string
    {
        $i = 1;

        return preg_replace_callback('~\?~', function () use (&$i): string {
            return '$'.$i++;
        }, $sql);
    }

    /**
     * @return \React\Promise\PromiseInterface<Result>
     */
    private function execStatementOnSubscription(string $sql, array $params): PromiseInterface
    {
        $sql = $this->fixSqlPreparedStatement($sql);

        $results = [];
        $deferred = new Deferred;
        $this->connection->executeStatement($sql, $params)
            ->subscribe(function ($row) use (&$results) {
                $results[] = $row;
            }, function (ErrorException $exception) use ($deferred, &$sql, &$params) {
                $deferred->reject($exception);
            }, function () use (&$results, $deferred) {
                $deferred->resolve($results);
            });

        return $deferred
            ->promise()
            ->then(function ($results) {
                return new Result($results);
            });
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
