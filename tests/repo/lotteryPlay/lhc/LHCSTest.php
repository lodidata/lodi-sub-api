<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/9
 * Time: 16:11
 */

/**
 * Class LHCQTest
 * 生肖
 */
use LotteryPlay\Logic;

class LHCSTest extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;
    //十二生肖
    public function testPlay513(){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 513, $odds = [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,], $bet = 1, $betMoney = 100, $playNumber = '狗');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 513, $odds = [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,], $bet = 2, $betMoney = 100, $playNumber = '鼠|狗');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    public function testPlay514(){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 514, $odds = [2.04, 2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,1.96,2.04], $bet = 1, $betMoney = 100, $playNumber = '狗');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 514, $odds = [2.04, 2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,1.96,2.04], $bet = 2, $betMoney = 100, $playNumber = '鼠|马');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

    }



}