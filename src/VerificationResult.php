<?php

/**
 * Representates the verification result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2025 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

/**
 * Representates the verification result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class VerificationResult
{
    const FIELD_NOT_VERIFIED = 'not-verified';
    const FIELD_VALID = 'valid';
    const FIELD_INVALID = 'invalid';

    /**
     * @var bool 
     */
    private bool $submittable;

    /**
     * @var bool
     */
    private bool $valid;

    /**
     * @var array 
     */
    private array $verifiedFields = [];

    /**
     * @var array 
     */
    private array $issues = [];

    /**
     * @var array|null
     */
    private ?array $debugInformation = null;

    /**
     * Constructs the object
     * 
     * @param bool $submittable
     * @param bool $valid
     * @param array $verifiedFields
     * @param array $issues
     * @param array|null $debugInformation
     */
    public function __construct(bool $submittable, bool $valid, array $verifiedFields, array $issues, ?array $debugInformation = null)
    {
        $this->submittable = $submittable;
        $this->valid = $valid;
        $this->verifiedFields = $verifiedFields;
        $this->issues = $issues;
        $this->debugInformation = $debugInformation;
    }

    /**
     * Returns true if the submission is submittable
     *
     * @return bool
     */
    public function isSubmittable(): bool
    {
        return ($this->submittable);
    }

    /**
     * Returns true if the submission is valid
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return ($this->valid);
    }

    /**
     * Returns the array with all verified fields
     * 
     * @return array
     */
    public function getVerifiedFields(): array
    {
        return $this->verifiedFields;
    }

    /**
     * Returns the verification status for the given field key
     * 
     * @param string $key
     * @return string
     */
    public function getVerifiedField(string $key): string
    {
        if (!isset($this->verifiedFields[$key])) {
            return self::FIELD_NOT_VERIFIED;
        }

        return $this->verifiedFields[$key];
    }

    /**
     * Returns true if the API returned issues
     * 
     * @return bool
     */
    public function hasIssues(): bool
    {
        return (!empty($this->issues));
    }

    /**
     * Returns the issues
     * 
     * @return array
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Returns the debug information or null if no debug information is available.
     *
     * @return array|null
     */
    public function getDebugInformation(): ?array
    {
        return $this->debugInformation;
    }
}