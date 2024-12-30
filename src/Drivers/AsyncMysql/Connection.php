<?php

namespace Brash\Dbal\Drivers\AsyncMysql;

use Brash\Dbal\DoctrineException;
use Brash\Dbal\Observer\CompletionEmitter;
use Brash\Dbal\Observer\ResultListenerInterface;
use Brash\Dbal\Observer\SqlResult;
use Doctrine\DBAL\Driver\API\MySQL\ExceptionConverter;
use Doctrine\DBAL\Driver\Result as DoctrineResult;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query;
use React\Mysql\MysqlClient;
use function React\Async\await;
use Doctrine\DBAL\Driver\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;


class Connection implements DoctrineConnection, ResultListenerInterface
{
    private int|null $lastInsertId = null;
    private readonly ExceptionConverterInterface $exceptionConverter;

    public function __construct(
        private readonly MysqlClient $connection,
        private readonly CompletionEmitter $completionEmitter
    ) {
        $this->exceptionConverter = new ExceptionConverter();
    }

    public function listen(SqlResult $result): void
    {
        $this->lastInsertId = $result->insertId;
    }

    public function prepare(string $sql): DoctrineStatement
    {
        try {
            return new Statement(
                $this->connection,
                $sql,
                $this
            );
        } catch (\Throwable $e) {
            throw MysqlException::new($e);
        }
    }

    public function query(string $sql): DoctrineResult
    {
        try {
            /**
             * @var \React\Mysql\MysqlResult
             */
            $result = await($this->connection->query($sql));

            $this->listen(new SqlResult(
                $result->insertId,
                $result->affectedRows,
                $result->resultFields,
                $result->resultRows,
                $result->warningCount
            ));

            return new Result($result);
        } catch (\Throwable $exception) {
            $this->close();
            var_dump($exception);


            throw $this->exceptionConverter->convert(new DoctrineException(
                $exception->getMessage(),
                null,
                $exception->getCode()
            ), new Query($sql, [], []));
        } finally {
            $this->completionEmitter->notifyCompleted($this);
        }
    }

    public function quote($value, $type = ParameterType::STRING): string
    {
        throw new \Error("Not implemented, use prepared statements");
    }

    public function exec(string $sql): int
    {
        try {
            $result = await($this->connection->query($sql));

            $this->listen(new SqlResult(
                $result->insertId,
                $result->affectedRows,
                $result->resultFields,
                $result->resultRows,
                $result->warningCount
            ));

            return $result->affectedRows;
        } catch (\Throwable $exception) {
            $this->close();

            var_dump($exception);
            
            throw $this->exceptionConverter->convert(new DoctrineException(
                $exception->getMessage(),
                null,
                $exception->getCode()
            ), new Query($sql, [], []));
        } finally {
            $this->completionEmitter->notifyCompleted($this);
        }
    }

    public function lastInsertId($name = null): int|string
    {
        return $this->lastInsertId;
    }

    public function beginTransaction(): void
    {
        $this->query('START TRANSACTION');
    }

    public function commit(): void
    {
        $this->query('COMMIT');
    }

    public function rollBack(): void
    {
        $this->query('ROLLBACK');
    }

    public function getServerVersion(): string
    {
        $values = array_values($this->query("SELECT @@version")->fetchOne());
        return array_pop($values);
    }

    public function getNativeConnection(): object
    {
        return $this->connection;
    }

    public function close(): void
    {
        $this->connection->close();
    }
}
