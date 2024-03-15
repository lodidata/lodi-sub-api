<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 二星
 */
class SSC2Test extends TestCase {
    const CATE = 10;

    // 直选复式
    public function testPlay59() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 59, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 59, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(100, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选和值
    public function testPlay60() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 60, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87], $bet = 1, $betMoney = 100, $playNumber = '0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 60, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(19, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选跨度
    public function testPlay61() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 61, $odds = [10,5.55,6.25,7.14,8.33,10,12.5,16.67,25,50], $bet = 1, $betMoney = 100, $playNumber = '0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 61, $odds = [10,5.55,6.25,7.14,8.33,10,12.5,16.67,25,50], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // 组选复式
    public function testPlay62() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 62, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '0|1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 62, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(45, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}