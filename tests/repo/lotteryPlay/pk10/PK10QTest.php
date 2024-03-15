<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * PK10 趣味玩法
 */
class PK10QTest extends TestCase {
    const CATE = 39;

    // 大小单双
    public function testPlay452() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 452, $odds = [12], $bet = 1, $betMoney = 100, $playNumber = '大,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 452, $odds = [12], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(12, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // 冠亚和值
    public function testPlay453() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [2.25,1.8,1.8,2.25,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], $bet = 1, $betMoney = 100, $playNumber = '和大');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [2.25,1.8,1.8,2.25,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], $bet = 1, $betMoney = 100, $playNumber = '和大|和小|和单|和双|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(21, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 冠亚季和值
    public function testPlay454() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 454, $odds = [120,120,60,40,30,24,17.14,15,13.33,12,12,12,12,13.33,15,17.14,24,30,40,60,120,120], $bet = 1, $betMoney = 100, $playNumber = '6');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 454, $odds = [120,120,60,40,30,24,17.14,15,13.33,12,12,12,12,13.33,15,17.14,24,30,40,60,120,120], $bet = 1, $betMoney = 100, $playNumber = '6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(22, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 龙虎斗
    public function testPlay455() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 455, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '冠十龙');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 455, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '冠十龙|冠十虎|亚九龙|亚九虎|季八龙|季八虎|四七龙|四七虎|五六龙|五六虎');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}