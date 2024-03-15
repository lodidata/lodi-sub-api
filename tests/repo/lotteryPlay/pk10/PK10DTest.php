<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * PK10 定位胆
 */
class PK10DTest extends TestCase {
    const CATE = 39;

    // 定位胆
      public function testPlay451() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,,,,,,,,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(100, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

}