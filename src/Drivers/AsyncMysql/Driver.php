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
use React\MySQL\Factory;
use React\Socket\ConnectorInterface;

class Driver extends AbstractMySQLDriver implements AcceptEmitterInterface
{
    private readonly Factory $factory;

    private ?CompletionEmitter $completionEmitter = null;

    public function __construct(?LoopInterface $loop = null, ConnectorInterface $connector = null)
    {
        $this->factory = new Factory($loop, $connector);
    }

    public function connect(
        #[\SensitiveParameter]
        array $params,
    ): DoctrineConnection {
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? '3306';
        $user = $params['user'] ?? '';
        $password = $params['password'] ?? '';
        $dbName = $params['dbname'] ?? null;
        $charset = $params['charset'] ?? "utf8mb4";
        $credentials = new Credentials(
            host: $host,
            port: $port,
            user: $user,
            password: $password,
            dbName: $dbName,
            options: ['charset' => $charset]
        );
        $reactConnection = $this->factory->createLazyConnection($credentials->toString());

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
