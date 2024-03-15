<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/10
 * Time: 13:50
 */

use LotteryPlay\Logic;

class LHCBTest extends \PHPUnit\Framework\TestCase
{


    const CATE = 51;
    //猜色波
    public function testPlay516(){
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 516, $odds = [2.08,3.06,3.06], $bet = 1, $betMoney = 100, $playNumber = '红波');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 516, $odds = [2.08,3.06,3.06], $bet = 2, $betMoney = 100, $playNumber = '红波|蓝波');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
    $logic->clear();
}

    public function testPlay517(){
    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,03,04,31,46,23,06')
        ->setRules($playId = 517, $odds = [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,], $bet = 1, $betMoney = 100, $playNumber = '红单');
    $logic->isValid(true);
    $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
    $logic->clear();


    $logic = new Logic;
    $logic->setCate(self::CATE)
        ->setPeriodCode('02,01,04,31,46,23,06')
        ->setRules($playId = 517, $odds = [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,], $bet = 2, $betMoney = 100, $playNumber = '红单|红小');
    $logic->isValid(true);
    $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
    $logic->clear();

}

   public function testPlay518(){
       $logic = new Logic;
       $logic->setCate(self::CATE)
           ->setPeriodCode('02,03,04,31,46,23,06')
           ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 1, $betMoney = 100, $playNumber = '红大单');
       $logic->isValid(true);
       $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
       $logic->clear();


       $logic = new Logic;
       $logic->setCate(self::CATE)
           ->setPeriodCode('02,03,04,31,46,23,06')
           ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 2, $betMoney = 100, $playNumber = '红大双|蓝小单');
       $logic->isValid(true);
       $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
       $logic->clear();
   }



}