<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Mockery\MockInterface;
use React\EventLoop\LoopInterface;

function createMockLoopInterface(): MockInterface|LoopInterface
{
    return mock(LoopInterface::class);
}
