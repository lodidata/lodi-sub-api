<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 五星
 */
class SSC5Test extends TestCase {
    const CATE = 10;

    // 直选复式
    public function testPlay1() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals($odds[0] * $bet * $betMoney, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,3,4,5')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1|2,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals($odds[0] * $bet * $betMoney, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选组合
    public function testPlay2() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 2, $odds = [100000, 10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals($odds[0] * $bet * $betMoney + $odds[1] * $bet * $betMoney + $odds[2] * $bet * $betMoney
        + $odds[3] * $bet * $betMoney + $odds[4] * $bet * $betMoney, $res[1] , print_r($logic->getErrors(),true));

        $this->assertEquals(5, $logic->getBetCount(), 'betCount:'.$logic->getBetCount());
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,3,4,5')
              ->setRules($playId = 2, $odds = [100000, 10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals($odds[1] * $bet * $betMoney + $odds[2] * $bet * $betMoney
        + $odds[3] * $bet * $betMoney + $odds[4] * $bet * $betMoney, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 2, $odds = [100000, 10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(500000, $logic->getBetCount(), 'betCount:'.$logic->getBetCount());
        $logic->clear();
    }

    // 通选复式
    public function testPlay3() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [5000, 500, 50], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        // print_r($logic->getWinBetCount());
        // $this->assertEquals($odds[0] * $bet * $betMoney + 2 * $odds[1] * $bet * $betMoney + 2 * $odds[2] * $bet * $betMoney, $res[1] , print_r($logic->getErrors(),true));
        $this->assertEquals(1, $logic->getBetCount(), 'betCount:'.$logic->getBetCount());
        // $this->assertEquals(5, array_sum(array_values($logic->getWinBetCount())), print_r($logic->getWinBetCount()));
        $logic->clear();
    }

    // 组选120
    public function testPlay4() {
        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 4, $odds = [833], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5');
        // $logic->isValid(true);
        // $res = $logic->run();
        // $this->assertEquals($odds[0] * $bet * $betMoney, $res[1], print_r($logic->getErrors(),true));
        // $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 4, $odds = [833], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(252, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选60
    public function testPlay5() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 5, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '1,1|2|3');
        $logic->isValid();
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 5, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '1|2,1|2|3');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 5, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|9,1|2|3');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 5, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0,1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $this->assertEquals(84, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 5, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(840, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选30
    public function testPlay6() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 6, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 6, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(360, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选20
    public function testPlay7() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 7, $odds = [5000], $bet = 1, $betMoney = 100, $playNumber = '0,1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 7, $odds = [5000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(360, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选10
    public function testPlay8() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 8, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 8, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选5
    public function testPlay9() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 9, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 9, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 百家乐
    public function testPlay10() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 10, $odds = [2.1,10,100,5.2,2.1,10,100,5.2], $bet = 1, $betMoney = 100, $playNumber = '庄');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 10, $odds = [2.1,10,100,5.2,2.1,10,100,5.2], $bet = 1, $betMoney = 100, $playNumber = '庄|庄对子|庄豹子|庄天王|闲|闲对子|闲豹子|闲天王');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(8, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 10, $odds = [2.1,10,100,5.2,2.1,10,100,5.2], $bet = 1, $betMoney = 100, $playNumber = '庄|庄对子|庄豹子|庄天王,闲|闲对子|闲豹子|闲天王');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(8, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
    
    // 一帆风顺
    public function testPlay11() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 11, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 11, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 好事成双
    public function testPlay12() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 12, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 12, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三星报喜
    public function testPlay13() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 13, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 13, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 四季发财
    public function testPlay14() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 14, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 14, $odds = [20000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}