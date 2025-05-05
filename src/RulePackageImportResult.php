<?php

/**
 * Representates the rule package import result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2025 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

/**
 * Representates the rule package import result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class RulePackageImportResult
{
    /**
     * @var bool 
     */
    private bool $successful;

    /**
     * @var bool
     */
    private bool $hashValidated;

    /**
     * Constructs the object
     * 
     * @param bool $successful
     * @param bool $hashValidated
     */
    public function __construct(bool $successful, bool $hashValidated)
    {
        $this->successful = $successful;
        $this->hashValidated = $hashValidated;
    }

    /**
     * Returns true if the import was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return ($this->successful);
    }

    /**
     * Returns true if the hash was validated
     * 
     * @return bool
     */
    public function isHashValidated(): bool
    {
        return ($this->hashValidated);
    }
}