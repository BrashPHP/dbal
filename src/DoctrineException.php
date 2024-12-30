<?php

declare(strict_types=1);

namespace Brash\Dbal;

use Throwable;
use Exception;

class DoctrineException extends Exception implements \Doctrine\DBAL\Driver\Exception
{
    /**
     * @param string         $message  The driver error message.
     * @param string|null    $sqlState The SQLSTATE the driver is in at the time the error occurred, if any.
     * @param int            $code     The driver specific error code if any.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct($message, /**
     * The SQLSTATE of the driver.
     */
    private $sqlState = null, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getSQLState(): string|null
    {
        return $this->sqlState;
    }
}
