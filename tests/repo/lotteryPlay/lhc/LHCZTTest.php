<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/10
 * Time: 14:13
 */
use LotteryPlay\Logic;


class LHCZTTestextends extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;

    //正特头尾
    public function testPlay519()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 519, $odds = [5.40,4.90,4.90,4.90,4.90], $bet = 1, $betMoney = 100, $playNumber = '头0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 519, $odds = [5.40,4.90,4.90,4.90,4.90], $bet = 2, $betMoney = 100, $playNumber = '头0|头1');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();
    }

    public function testPlay520()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 520, $odds = [12.25, 9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,], $bet = 1, $betMoney = 100, $playNumber = '尾0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,01,04,31,46,23,06')
            ->setRules($playId = 520, $odds = [12.25, 9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,], $bet = 2, $betMoney = 100, $playNumber = '尾0|尾1');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testPlay521()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 521, $odds = [2.30, 2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,], $bet = 1, $betMoney = 100, $playNumber = '尾0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 521, $odds = [2.30, 2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,], $bet = 2, $betMoney = 100, $playNumber = '尾0|尾3');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();
    }
}