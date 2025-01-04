<?php

namespace Brash\Dbal\Drivers\AsyncMysql;

use Brash\Dbal\AsyncConnectionInterface;
use Brash\Dbal\DoctrineException;
use Brash\Dbal\Observer\CompletionEmitter;
use Brash\Dbal\Observer\ResultListenerInterface;
use Brash\Dbal\Observer\SqlResult;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\MySQL\ExceptionConverter;
use Doctrine\DBAL\Driver\Result as DoctrineResult;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query;
use React\Mysql\MysqlClient;

use function React\Async\await;

class Connection implements AsyncConnectionInterface, ResultListenerInterface
{
    private ?int $lastInsertId = null;

    private readonly ExceptionConverterInterface $exceptionConverter;

    public function __construct(
        private readonly MysqlClient $connection,
        private readonly CompletionEmitter $completionEmitter
    ) {
        $this->exceptionConverter = new ExceptionConverter;
    }

    #[\Override]
    public function listen(SqlResult $result): void
    {
        $this->lastInsertId = $result->insertId;
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function quote($value, $type = ParameterType::STRING): string
    {
        throw new \Error('Not implemented, use prepared statements');
    }

    #[\Override]
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

    #[\Override]
    public function lastInsertId($name = null): int|string
    {
        return $this->lastInsertId;
    }

    #[\Override]
    public function beginTransaction(): void
    {
        $this->query('START TRANSACTION');
    }

    #[\Override]
    public function commit(): void
    {
        $this->query('COMMIT');
    }

    #[\Override]
    public function rollBack(): void
    {
        $this->query('ROLLBACK');
    }

    #[\Override]
    public function getServerVersion(): string
    {
        return $this->query('SELECT @@version')->fetchOne();
    }

    #[\Override]
    public function getNativeConnection(): object
    {
        return $this->connection;
    }

    #[\Override]
    public function close(): void
    {
        $this->connection->close();
    }
}
