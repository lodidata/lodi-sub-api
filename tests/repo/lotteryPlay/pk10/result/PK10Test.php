<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * PK10 传统车位
 */
class PK10Test extends TestCase {
    const CATE = 39;

    // '快捷', '定位胆', '定位胆'
    public function testSuccPlay451() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5,6,7,8,9,10');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(10, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位胆', '定位胆'
    public function testErrorPlay451() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 451, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '10,9,8');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '大小单双', '大小单双'
    public function testSuccPlay452() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,10,4,3,5,6,7,8,9,2')
              ->setRules($playId = 452, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '小,大,双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 452, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '单,,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '大小单双', '大小单双'
    public function testErrorPlay452() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 452, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '双,单,大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,10,4,5,6,7,8,9,3')
              ->setRules($playId = 452, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '双,单,小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '龙虎斗', '冠亚和值'
    public function testSuccPlay453() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,9,3,4,5,6,7,8,2,1')
              ->setRules($playId = 453, $odds = [1,1.8,1.8,2.25,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], 
                $bet = 1, $betMoney = 1, $playNumber = '和大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [100,1,1.8,2.25,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], 
                $bet = 1, $betMoney = 1, $playNumber = '和小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,2,3,1,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [100,0,0,1,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], 
                $bet = 1, $betMoney = 1, $playNumber = '和双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [100,0,1,0,45,45,22.5,22.5,15,15,11.25,11.25,9,11.25,11.25,15,15,22.5,22.5,45,45], 
                $bet = 1, $betMoney = 1, $playNumber = '和单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '龙虎斗', '冠亚和值'
    public function testErrorPlay453() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 453, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '双,单,大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,10,4,5,6,7,8,9,3')
              ->setRules($playId = 453, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '双,单,小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '猜和值', '冠亚季和值'
    public function testSuccPlay454() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 454, $odds = [1,120,60,40,30,24,17.14,15,13.33,12,12,12,12,13.33,15,17.14,24,30,40,60,120,120], $bet = 1, $betMoney = 1, $playNumber = '6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '猜和值', '冠亚季和值'
    public function testErrorPlay454() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 454, $odds = [120,120,60,40,30,24,17.14,15,13.33,12,12,12,12,13.33,15,17.14,24,30,40,60,120,120], $bet = 1, $betMoney = 1, $playNumber = '7');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // '快捷', '龙虎斗', '龙虎斗'
    public function testSuccPlay455() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 455, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '冠十龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 455, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '冠十虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '龙虎斗', '龙虎斗'
    public function testErrorPlay455() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 455, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '冠十虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // '标准', '猜第一', '猜第一'
    public function testSuccPlay456() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 456, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '10');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 456, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '猜第一', '猜第一'
    public function testErrorPlay456() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 456, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '猜前二', '猜前二'
    public function testSuccPlay457() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 457, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '10,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 457, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|3,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '猜前二', '猜前二'
    public function testErrorPlay457() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,3,4,5,6,7,8,9,1')
              ->setRules($playId = 457, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
}