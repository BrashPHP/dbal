<?php

declare(strict_types=1);

namespace Brash\Dbal\Drivers\AsyncPostgres;

use Brash\Dbal\DoctrineException;
use Doctrine\DBAL\Query;
use PgAsync\ErrorException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

final class SubscriptionPromiseBridge
{
    public function __construct(private Connection $connection) {}

    /**
     * @return \React\Promise\PromiseInterface<Result>
     */
    public function bridge(string $sql, array $params = []): PromiseInterface
    {
        $sql = $this->fixSqlPreparedStatement($sql);

        $results = [];
        $deferred = new Deferred;
        $connection = $this->connection;
        $exceptionConverter = $connection->getExceptionConverter();

        $connection
            ->getNativeConnection()
            ->executeStatement($sql, $params)
            ->subscribe(function ($row) use (&$results) {
                $results[] = $row;
            }, function (ErrorException $exception) use ($deferred, &$sql, &$params, $exceptionConverter) {
                $errorResponse = $exception->getErrorResponse();
                $code = 0;
                foreach ($errorResponse->getErrorMessages() as $messageLine) {
                    if ($messageLine['type'] === 'C') {
                        $code = $messageLine['message'];
                    }
                }

                $exception = $exceptionConverter->convert(
                    new DoctrineException($exception->getMessage(), \strval($code)),
                    new Query(
                        $sql,
                        $params,
                        []
                    )
                );

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

    private function fixSqlPreparedStatement(string $sql): string
    {
        $i = 1;

        return preg_replace_callback('~\?~', function () use (&$i): string {
            return '$'.$i++;
        }, $sql);
    }
}
