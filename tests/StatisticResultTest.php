<?php

namespace Mosparo\ApiClient;

use PHPUnit\Framework\TestCase;

class StatisticResultTest extends TestCase
{
    public function testStatisticResult()
    {
        $byDate = [
            ['2021-04-29' => ['numberOfValidSubmissions' => 2, 'numberOfSpamSubmissions' => 5]]
        ];
        $statisticResult = new StatisticResult(10, 20, $byDate);

        $this->assertEquals(10, $statisticResult->getNumberOfValidSubmissions());
        $this->assertEquals(20, $statisticResult->getNumberOfSpamSubmissions());
        $this->assertEquals($byDate, $statisticResult->getNumbersByDate());
    }
}
