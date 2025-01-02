<?php

declare(strict_types=1);

namespace Brash\Dbal\Drivers\AsyncSqlite;

use Brash\Dbal\Observer\AcceptEmitterInterface;
use Brash\Dbal\Observer\CompletionEmitter;
use Clue\React\SQLite\Factory;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\Connection as DoctrineConnection;

/**
 * Use :memory: as db name to set a db in memory
 */
class Driver extends AbstractSQLiteDriver implements AcceptEmitterInterface
{
    private ?CompletionEmitter $completionEmitter = null;

    #[\Override]
    public function connect(
        #[\SensitiveParameter]
        array $params,
    ): DoctrineConnection {
        $dbName = $params['dbname'] ?? null;

        $reactConnection = (new Factory)->openLazy($dbName, options: $params);

        \assert($this->completionEmitter instanceof CompletionEmitter);

        return new Connection(
            $reactConnection,
            $this->completionEmitter
        );
    }

    #[\Override]
    public function accept(CompletionEmitter $completionEmitter): void
    {
        $this->completionEmitter = $completionEmitter;
    }
}
