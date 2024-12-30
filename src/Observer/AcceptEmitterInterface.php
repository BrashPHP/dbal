<?php

namespace Brash\Dbal\Observer;

interface AcceptEmitterInterface
{
    public function accept(CompletionEmitter $completionEmitter): void;
}