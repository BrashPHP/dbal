<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

/**
 * @template T
 */
abstract class PoolItem
{
    private int $lastUsedAt;

    private bool $isClosed = false;

    public bool $isLocked = false;

    /**
     * @param  T  $item
     */
    public function __construct(public readonly object $item)
    {
        $this->lastUsedAt = \time();
    }

    public function lock(): void
    {
        $this->isLocked = true;
        $this->setLastUsedAt(\time());
    }

    public function unlock(): void
    {
        $this->isLocked = false;
    }

    abstract protected function onClose(): void;

    abstract public function validate(): bool;

    public function close(): void
    {
        $this->isClosed = true;
        $this->onClose();
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /** @return T */
    public function reveal(): object
    {
        return $this->item;
    }

    public function getLastUsedAt(): int
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(int $time): void
    {
        $this->lastUsedAt = $time;
    }
}
