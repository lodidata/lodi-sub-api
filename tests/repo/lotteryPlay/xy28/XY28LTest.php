<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 28类 龙虎斗
 */
class XY28LTest extends TestCase {
    const CATE = 1;
    
    // 龙虎斗
    public function testPlay159() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 159, $odds = [2.22], $bet = 1, $betMoney = 100, $playNumber = '百十龙');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 159, $odds = [2.22], $bet = 1, $betMoney = 100, $playNumber = '百十龙|百十虎|百个龙|百个虎|十个龙|十个虎');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(6, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}