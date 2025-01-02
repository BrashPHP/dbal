<?php

namespace Brash\Dbal\Observer;

interface CompletionObserverInterface
{
    public function update(\Doctrine\DBAL\Driver\Connection $connection): void;
}
