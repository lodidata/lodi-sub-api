<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 三星
 */
class SSC3Test extends TestCase {
    const CATE = 10;

    // 直选复式
    public function testPlay28() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 28, $odds = [1000], $bet = 1, $betMoney = 100, $playNumber = '0,1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 28, $odds = [1000], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选组合
    public function testPlay29() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 29, $odds = [1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '0,1,2');
        $logic->isValid(true);
        $this->assertEquals(3, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 29, $odds = [1000, 100, 10], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(3000, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选和值
    public function testPlay31() {

        $this->assertEquals(28, count([3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00]));

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 31, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 31, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(28, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选跨度
    public function testPlay32() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 32, $odds = [2, 2, 2, 2, 2, 2, 2, 2, 2, 2], $bet = 1, $betMoney = 100, $playNumber = '0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 32, $odds = [2, 2, 2, 2, 2, 2, 2, 2, 2, 2], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组三包号
    public function testPlay33() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 33, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0|1');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 33, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组三直选
    public function testPlay34() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 34, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0,1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 34, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组六包号
    public function testPlay35() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 35, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0|1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 35, $odds = [166.67], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(120, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后三豹子
    public function testPlay36() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 36, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '豹子通选');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 36, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '豹子通选|000|111|222|333|444|555|666|777|888|999');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后三顺子
    public function testPlay37() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 37, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '顺子通选');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 37, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '顺子通选|012|123|234|345|456|567|678|789|890|901');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后三对子
    public function testPlay38() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 38, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 100, $playNumber = '对子通选');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 38, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 100, $playNumber = '对子通选|00|11|22|33|44|55|66|77|88|99');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后三对子
    public function testPlay39() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 38, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 100, $playNumber = '对子通选');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 38, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 100, $playNumber = '对子通选|00|11|22|33|44|55|66|77|88|99');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}