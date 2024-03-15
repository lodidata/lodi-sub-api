<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/10
 * Time: 14:35
 */
use LotteryPlay\Logic;
class LHCZBTestextends extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;
//正B定位(双面)
    public function testPlay528()
{
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 528, $odds = [2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,], $bet = 1, $betMoney = 100, $playNumber = '大');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 528, $odds = [2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,], $bet = 1, $betMoney = 100, $playNumber = '大|双');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
    $logic->clear();
}

   /* public function testPlay523()
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
}*/
}