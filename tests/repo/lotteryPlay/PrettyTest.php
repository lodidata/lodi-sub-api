<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 *      1 => '\\LotteryPlay\\xy28',
        5 => '\\LotteryPlay\\q3',
        10 => '\\LotteryPlay\\ssc',
        24 => '\\LotteryPlay\\m11n5',
        39 => '\\LotteryPlay\\pk10',
 */
class PrettyTest extends TestCase {

    public function testSSCPretty1() {
        $logic = new Logic;
        $res = $logic->getPretty(39, 456, '8');
        $this->assertEquals(1, 1, print_r($logic->getErrors(),true));
    }
}