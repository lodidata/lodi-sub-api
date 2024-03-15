<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/9
 * Time: 11:49
 */
use LotteryPlay\Logic;

/**
 * Class LHCQTest
 * 球号
 */


class LHCQTest extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;
    //猜特码 特码号
    public function testPlay510(){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 510, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '06');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 510, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 2, $betMoney = 100, $playNumber = '06|05');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
/*
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,1,3')
            ->setRules($playId = 156, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(28, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();*/
    }

    //猜正码 正码号
    public function testPlay512(){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 512, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '06');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 512, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 2, $betMoney = 100, $playNumber = '06|05');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
        /*
                $logic = new Logic;
                $logic->setCate(self::CATE)
                    ->setPeriodCode('1,1,3')
                    ->setRules($playId = 156, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
                $logic->isValid(true);
                $logic->run();
                $this->assertEquals(28, $logic->getBetCount(), print_r($logic->getErrors(),true));
                $logic->clear();*/
    }



}