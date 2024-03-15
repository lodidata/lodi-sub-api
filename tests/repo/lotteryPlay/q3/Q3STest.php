<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 快三 三不同号 三同号
 */
class Q3STest extends TestCase {
    const CATE = 5;

    // 三不同标准
    public function testPlay605() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 605, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 605, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1|2|3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 605, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(4, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 605, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(20, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三不同胆拖
    public function testPlay606() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1,1|2');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1,2|3');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(3, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 606, $odds = [36], $bet = 1, $betMoney = 100, $playNumber = '1|2,3|4|5|6');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(4, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三同号
    public function testPlay607() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 100, $playNumber = '111|222');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 100, $playNumber = '三同号通选');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 100, $playNumber = '111|222|333|444|555|666|三同号通选');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(7, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三连号
    public function testPlay608() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 608, $odds = [36, 36, 36, 36, 9], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 608, $odds = [36, 36, 36, 36, 9], $bet = 1, $betMoney = 100, $playNumber = '三连号通选');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 608, $odds = [36, 36, 36, 36, 9], $bet = 1, $betMoney = 100, $playNumber = '123|234|345|456|三连号通选');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(5, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 点数
    public function testPlay609() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 609, $odds = [2.05, 2.05, 2.05, 2.05, 216, 72, 36, 21.6, 14.4, 10.29, 8.64, 8, 8, 8.64, 10.29, 14.4, 21.6, 36, 72, 216], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 609, $odds = [2.05, 2.05, 2.05, 2.05, 216, 72, 36, 21.6, 14.4, 10.29, 8.64, 8, 8, 8.64, 10.29, 14.4, 21.6, 36, 72, 216], $bet = 1, $betMoney = 100, $playNumber = '大');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 609, $odds = [2.05, 2.05, 2.05, 2.05, 216, 72, 36, 21.6, 14.4, 10.29, 8.64, 8, 8, 8.64, 10.29, 14.4, 21.6, 36, 72, 216], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(20, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 骰宝
    public function testPlay610() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 610, $odds = [2.37,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 610, $odds =  [2.37,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 100, $playNumber = '骰宝1');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 610, $odds =  [2.37,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 100, $playNumber = '骰宝1|骰宝2|骰宝3|骰宝4|骰宝5|骰宝6|短牌11|短牌22|短牌33|短牌44|短牌55|短牌66|长牌12|长牌13|长牌14|长牌15|长牌16|长牌23|长牌24|长牌25|长牌26|长牌34|长牌35|长牌36|长牌45|长牌46|长牌56');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(27, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

    }
}