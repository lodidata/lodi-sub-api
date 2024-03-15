<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class LogicTest extends TestCase {

    public function testValid() {
        $logic = new Logic;
        
        $logic->setCate(10)
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);
        $this->assertEquals(true, $logic->isValid(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(10)
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6', $maxPrize = 1000000);
        $this->assertEquals(false, $logic->isValid());
        $logic->clear();

        $logic->setCate(12)
              ->setRules($playId = 99999, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6', $maxPrize = 1000000);
        $this->assertEquals(false, $logic->isValid(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(10)
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = ',2,3,4,5', $maxPrize = 1000000);

        $this->assertEquals(false, $logic->isValid(), print_r($logic->getErrors(),true));
        $logic->clear();
    }


    public function testRun() {
        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('0,2,0,0,8')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);

        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));

        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1000000, $res[1], print_r($logic->getErrors(),true));

        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 100);
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(100, $res[1], print_r($logic->getErrors(),true));
    }

    public function testGetWin() {
        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('0,2,0,0,8')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);

        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(true, !empty($logic->getWin()));
    }

    public function testGetWinBetCount() {
        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('0,2,0,0,8')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);

        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(true, !empty($logic->getWinBetCount()));
    }

    public function testGetBetCount() {
        $logic = new Logic;
        $logic->setCate(10)
              ->setPeriodCode('0,2,0,0,8')
              ->setRules($playId = 1, $odds = [10000], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5', $maxPrize = 1000000);

        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(true, !empty($logic->getBetCount()));
    }
}