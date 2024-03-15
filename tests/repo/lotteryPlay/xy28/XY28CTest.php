<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 28类 猜数字
 */
class XY28CTest extends TestCase {
    const CATE = 1;
    

    // 和值
    public function testPlay156() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 156, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '0');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3')
              ->setRules($playId = 156, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(28, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 和值包三
    public function testPlay157() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 157, $odds = [3.7], $bet = 1, $betMoney = 100, $playNumber = '0|1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,1,3')
        //       ->setRules($playId = 157, $odds = [3.7], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(28, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }
}