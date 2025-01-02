<?php

declare(strict_types=1);

namespace Brash\Dbal\Drivers\AsyncPostgres;

use Brash\Dbal\Credentials;
use Brash\Dbal\Observer\AcceptEmitterInterface;
use Brash\Dbal\Observer\CompletionEmitter;
use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Doctrine\DBAL\Driver\Connection as DoctrineConnection;
use PgAsync\Client;

class Driver extends AbstractPostgreSQLDriver implements AcceptEmitterInterface
{
    private ?CompletionEmitter $completionEmitter = null;

    #[\Override]
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

        $credentials = new Credentials(
            host: $host,
            port: (string) $port,
            user: $user,
            password: $password,
            dbName: $dbName,
            options: $options
        );
        $client = new Client([
            'host' => $credentials->host,
            'port' => $credentials->port,
            'user' => $credentials->user,
            'password' => $credentials->password,
            'database' => $credentials->dbName,
        ]);

        $conn = $client->getIdleConnection();

        \assert($this->completionEmitter instanceof CompletionEmitter);

        return new Connection(
            $conn,
            $this->completionEmitter
        );
    }

    #[\Override]
    public function accept(CompletionEmitter $completionEmitter): void
    {
        $this->completionEmitter = $completionEmitter;
    }
}
