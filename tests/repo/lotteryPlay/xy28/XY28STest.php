<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 28类 色波豹子
 */
class XY28STest extends TestCase {
    const CATE = 1;
    
    // 色波
    public function testPlay152() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 152, $odds = [3.01, 3.88, 3.88], $bet = 1, $betMoney = 100, $playNumber = '红波');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 152, $odds = [3.01, 3.88, 3.88], $bet = 1, $betMoney = 100, $playNumber = '红波|绿波|蓝波');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(3, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 豹子
    public function testPlay153() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 153, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '000');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 153, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 100, $playNumber = '豹子通选|000|111|222|333|444|555|666|777|888|999');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 顺子
    public function testPlay154() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 154, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 100, $playNumber = '顺子通选');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 154, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 100, $playNumber = '顺子通选|012|123|234|345|456|567|678|789|890|901');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 对子
    public function testPlay155() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 155, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 100, $playNumber = '00');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 155, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 100, $playNumber = '对子通选|00|11|22|33|44|55|66|77|88|99');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}

