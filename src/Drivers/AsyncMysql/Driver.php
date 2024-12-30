<?php

declare(strict_types=1);

namespace Brash\Dbal\Drivers\AsyncMysql;

use Brash\Dbal\Credentials;
use Brash\Dbal\Drivers\AsyncMysql\Connection;
use Brash\Dbal\Observer\AcceptEmitterInterface;
use Brash\Dbal\Observer\CompletionEmitter;
use Doctrine\DBAL\Driver\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use React\EventLoop\LoopInterface;
use React\MySQL\Io\Factory;
use React\Mysql\MysqlClient;
use React\Socket\ConnectorInterface;
use function React\Async\await;

class Driver extends AbstractMySQLDriver implements AcceptEmitterInterface
{

    private ?CompletionEmitter $completionEmitter = null;


    public function connect(
        #[\SensitiveParameter]
        array $params,
    ): DoctrineConnection {
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? '3306';
        $user = $params['user'] ?? '';
        $password = $params['password'] ?? '';
        $dbName = $params['dbname'] ?? null;
        $options = [];
        if (isset($params['charset'])) {
            $options['charset'] = $params['charset'];
        }
        $credentials = new Credentials(
            host: $host,
            port: (string) $port,
            user: $user,
            password: $password,
            dbName: $dbName,
            options: $options
        );
        $reactConnection = new MysqlClient($credentials->toString());

        \assert($this->completionEmitter instanceof CompletionEmitter);

        return new Connection(
            $reactConnection,
            $this->completionEmitter
        );
    }

    public function accept(CompletionEmitter $completionEmitter): void
    {
        $this->completionEmitter = $completionEmitter;
    }
}
