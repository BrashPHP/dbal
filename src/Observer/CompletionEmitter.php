<?php

namespace Brash\Dbal\Observer;

class CompletionEmitter
{
    /**
     *
     * List of observers for any connection completion
     * @var \SplObjectStorage|CompletionObserverInterface[]
     */
    private readonly \SplObjectStorage $observers;
    public function __construct()
    {
        $this->observers = new \SplObjectStorage();
    }

    public function includeObserver(CompletionObserverInterface $observer): void
    {
        $this->observers->attach($observer);
    }


    public function notifyCompleted(\Doctrine\DBAL\Driver\Connection $connection): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($connection);
        }
    }
}
