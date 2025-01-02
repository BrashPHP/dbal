<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

use Doctrine\DBAL\Driver\Connection;

/**
 * @template-extends PoolItem<Connection>
 * @extends parent<Connection>
 */
final class ConnectionItem extends PoolItem
{
    protected function onClose(): void
    {
        $this->item->getNativeConnection()?->close();
    }
    public function validate(): bool
    {
        return $this->item instanceof Connection;
    }
}
