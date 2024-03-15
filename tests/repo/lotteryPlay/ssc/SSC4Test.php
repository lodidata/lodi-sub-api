<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 四星
 */
class SSC4Test extends TestCase {
    const CATE = 10;
    // 后四复式
    public function testPlay15() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 15, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 15, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四复式
    public function testPlay16() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 16, $odds = [10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $this->assertEquals(4, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 16, $odds = [10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(40000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四复式
    public function testPlay17() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 17, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 17, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组合
    public function testPlay18() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 18, $odds = [10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $this->assertEquals(4, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 18, $odds = [10000, 1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(40000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选24
    public function testPlay19() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 19, $odds = [416], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 19, $odds = [416], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(210, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选12
    public function testPlay20() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 20, $odds = [416], $bet = 1, $betMoney = 100, $playNumber = '0,1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 20, $odds = [416], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(360, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选6
    public function testPlay22() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 22, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 22, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(45, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选4
    public function testPlay23() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 23, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 23, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选24
    public function testPlay24() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 24, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 24, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(210, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选12
    public function testPlay25() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 25, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0,1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 25, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(360, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选6
    public function testPlay26() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 26, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 26, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(45, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选4
    public function testPlay27() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 27, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 27, $odds = [1666], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}