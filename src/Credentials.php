<?php

declare(strict_types=1);

namespace Brash\Dbal;

final readonly class Credentials
{
    public function __construct(
        public string $host,
        public string $port,
        public string $user,
        public string $password,
        public string $dbName,
        /**
         * @var array<string,string> $options
         */
        public array $options = []
    ) {}

    public function toString(): string
    {
        $asString = sprintf(
            '%s:%s@%s:%d/%s',
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->dbName
        );

        if ($this->options !== []) {
            $asString .= '?'.\http_build_query($this->options);
        }

        if (str_starts_with($asString, ':@')) {
            return rawurldecode(
                substr($asString, 2)
            );
        }

        return rawurldecode(
            str_replace(':@', '@', $asString)
        );
    }
}
