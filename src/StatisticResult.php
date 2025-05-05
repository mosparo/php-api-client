<?php

/**
 * Representates the statistic result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2025 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

/**
 * Representates the statistic result from the mosparo API.
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class StatisticResult
{
    /**
     * @var int
     */
    private int $numberOfValidSubmissions = 0;

    /**
     * @var int
     */
    private int $numberOfSpamSubmissions = 0;

    /**
     * @var array 
     */
    private array $numbersByDate = [];

    /**
     * Constructs the object
     * 
     * @param int $numberOfValidSubmissions
     * @param int $numberOfSpamSubmissions
     * @param array $numbersByDate
     */
    public function __construct(int $numberOfValidSubmissions, int $numberOfSpamSubmissions, array $numbersByDate)
    {
        $this->numberOfValidSubmissions = $numberOfValidSubmissions;
        $this->numberOfSpamSubmissions = $numberOfSpamSubmissions;
        $this->numbersByDate = $numbersByDate;
    }

    /**
     * Returns the number of valid submissions
     *
     * @return int
     */
    public function getNumberOfValidSubmissions(): int
    {
        return $this->numberOfValidSubmissions;
    }

    /**
     * Returns the number of spam submissions
     * 
     * @return int
     */
    public function getNumberOfSpamSubmissions(): int
    {
        return $this->numberOfSpamSubmissions;
    }

    /**
     * Get the numbers of submissions by date
     * 
     * @return array
     */
    public function getNumbersByDate(): array
    {
        return $this->numbersByDate;
    }
}