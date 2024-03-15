<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 快三 二不同号 二同号
 */
class Q3ETest extends TestCase {
    const CATE = 5;

    // 二不同标准
    public function testPlay601() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 601, $odds = [7.2], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 601, $odds = [7.2], $bet = 1, $betMoney = 100, $playNumber = '1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 601, $odds = [7.2], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(15, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 二不同胆拖
    public function testPlay602() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 602, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 602, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 602, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1,2');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 602, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(5, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 二同号标准
    public function testPlay603() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 603, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 603, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 603, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11,1|2');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 603, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11|22|33|44|55|66,1|2|3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(30, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 二同号复式
    public function testPlay604() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 604, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 604, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11*|22*');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 604, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11*');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 604, $odds = [100], $bet = 1, $betMoney = 100, $playNumber = '11*|22*|33*|44*|55*|66*');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(6, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}