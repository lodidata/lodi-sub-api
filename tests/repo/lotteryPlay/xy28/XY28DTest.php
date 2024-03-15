<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 28类 大小单双
 */
class XY28DTest extends TestCase {
    const CATE = 1;
    
    // 大小单双
    public function testPlay151() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 151, $odds = [2.00, 2.00, 2.00, 2.00, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86], $bet = 1, $betMoney = 100, $playNumber = '大');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 151, $odds = [2.00, 2.00, 2.00, 2.00, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双|小单|小双|大单|大双|极小|极大');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}

