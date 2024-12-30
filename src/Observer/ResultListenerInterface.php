<?php

namespace Brash\Dbal\Observer;

interface ResultListenerInterface
{
    public function listen(SqlResult $result);
}
