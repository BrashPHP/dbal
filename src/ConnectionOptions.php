<?php

declare(strict_types=1);

namespace Brash\Dbal;

class ConnectionOptions
{
    public function __construct(public int $keepAliveIntervalSec = 0) {}
}
