<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/10
 * Time: 15:34
 */
use LotteryPlay\Logic;

class LHCRBTest extends \PHPUnit\Framework\TestCase
{

    const CATE = 51;
//任选不中
    public function testPlay540()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 540, $odds = [2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,2.24,], $bet = 1, $betMoney = 100, $playNumber = '01|02|03|04|05');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        /*  $logic = new Logic;
          $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 534, $odds = [2.43, 2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,], $bet = 1, $betMoney = 100, $playNumber = '01|02|03|04|05');
          $logic->isValid(true);
          $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
          $logic->clear();*/
    }

    public function testPlay541()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 541, $odds = [2.34, 2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,2.34,], $bet = 1, $betMoney = 100, $playNumber = '01|02|03|04|05|06');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        /*  $logic = new Logic;
          $logic->setCate(self::CATE)
              ->setPeriodCode('02,01,04,31,46,23,06')
              ->setRules($playId = 523, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01|19');
          $logic->isValid(true);
          $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
          $logic->clear();*/

    }

    public function testPlay542()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('02,03,04,31,46,23,06')
            ->setRules($playId = 542, $odds = [2.33, 2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,2.33,], $bet = 1, $betMoney = 100, $playNumber = '01|02|03|04|05|06|07');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(), true));
        $logic->clear();


        /* $logic = new Logic;
         $logic->setCate(self::CATE)
             ->setPeriodCode('02,03,04,31,46,23,06')
             ->setRules($playId = 524, $odds = [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00], $bet = 1, $betMoney = 100, $playNumber = '01|08');
         $logic->isValid(true);
         $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(), true));
         $logic->clear();*/
    }
}
