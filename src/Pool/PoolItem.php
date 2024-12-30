<?php

declare(strict_types=1);

namespace Brash\Dbal\Pool;

/**
 * @template T
 */
abstract class PoolItem
{
    private int $lastUsedAt;
    private bool $isClosed;
    /** @var T $item */
    public readonly object $item;

    public bool $isLocked;

    /**
     * @param T $item
     */
    public function __construct(object $item)
    {
        $this->item = $item;
        $this->lastUsedAt = \time();
        $this->isClosed = false;
        $this->isLocked = false;
    }

    public function lock(): void
    {
        $this->isLocked = true;
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
