<?php

declare(strict_types=1);

namespace Brash\Dbal;

use Doctrine\DBAL\Driver\Connection;

interface AsyncConnectionInterface extends Connection
{
    public function close(): void;
}
