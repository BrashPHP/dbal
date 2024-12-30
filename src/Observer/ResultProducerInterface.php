<?php

namespace Brash\Dbal\Observer;

interface ResultProducerInterface
{
    public function notifyResult(SqlResult $result);
}
