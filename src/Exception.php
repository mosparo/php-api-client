<?php

/**
 * Exception for the mosparo PHP client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2025 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

/**
 * Exception for the mosparo PHP client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class Exception extends \Exception
{
    protected ?array $debugInformation;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?array $debugInformation = null)
    {
        parent::__construct($message, $code, $previous);

        $this->debugInformation = $debugInformation;
    }

    public function getDebugInformation(): ?array
    {
        return $this->debugInformation;
    }
}