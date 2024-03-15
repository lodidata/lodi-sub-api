<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/10
 * Time: 14:31
 */

use LotteryPlay\Logic;

class LHCZATestextends extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;

    //正A定位（单号）
    public function testPlay522()
{
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 522, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '08');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 522, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01|11');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();
}

    public function testPlay523()
{
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 523, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,01,04,31,46,23,06')
        ->setRules($playId = 523, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01|19');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();

}

    public function testPlay524()
{
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 524, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 524, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01|08');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();
}
}