<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 定位胆 / 大小单双
 */
class SSCDTest extends TestCase {
    const CATE = 10;

    // 定位胆
    public function testPlay69() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 69, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '0,0,0,0,0');
        $logic->isValid(true);
        $this->assertEquals(5, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 69, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '0,,,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 69, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(50, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 单球
    public function testPlay80() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 80, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大,,,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 80, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双,大|小|单|双,大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(20, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后二
    public function testPlay81() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 81, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大,小');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 81, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(16, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前二
    public function testPlay82() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 82, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大,小');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 82, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(16, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后三
    public function testPlay83() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 83, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大,小,小');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 83, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(64, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前三
    public function testPlay84() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 84, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大,小,小');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 84, $odds = [4], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(64, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}