<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

final readonly class ConnectionPoolOptions
{
    public const int DEFAULT_MAX_CONNECTIONS = 100;

    public const int DEFAULT_IDLE_TIMEOUT = 60;

    public function __construct(
        public int $maxConnections = self::DEFAULT_MAX_CONNECTIONS,
        public int $idleTimeout = self::DEFAULT_IDLE_TIMEOUT,
        public int $maxRetries = 7,
        public int $discardIdleConnectionsIn = 1,
        public int $minConnections = 1,
        public int $keepAliveIntervalSec = 0
    ) {
        if ($this->idleTimeout < 1) {
            throw new \Error('The idle timeout must be 1 or greater');
        }

        if ($this->minConnections < 1) {
            throw new \Error('Pool must contain at least one connection');
        }

        if ($this->maxConnections < 1) {
            throw new \Error('Pool must contain at least one connection');
        }
    }
}
