<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 28类
 */
class XY28Test extends TestCase {
    const CATE = 1;

    // '快捷', '大小单双', '大小单双'
    public function testSuccPlay151() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 151, $odds = [2.00, 1, 2.00, 2.00, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86]
                , $bet = 1, $betMoney = 1, $playNumber = '小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 151, $odds = [2.00, 1, 2.00, 2.00, 3.72, 1, 4.33, 3.72, 17.86, 17.86]
                , $bet = 1, $betMoney = 1, $playNumber = '小双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 151, $odds = [2.00, 0, 2.00, 1, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86]
                , $bet = 1, $betMoney = 1, $playNumber = '双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('7,9,9')
              ->setRules($playId = 151, $odds = [2.00, 0, 2.00, 1, 3.72, 4.33, 1, 3.72, 17.86, 17.86]
                , $bet = 1, $betMoney = 1, $playNumber = '大单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('7,8,9')
              ->setRules($playId = 151, $odds = [2.00, 0, 2.00, 1, 3.72, 4.33, 4.33, 1, 17.86, 17.86]
                , $bet = 1, $betMoney = 1, $playNumber = '大双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '大小单双', '大小单双'
    public function testErrorPlay151() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 151, $odds = [2.00, 2.00, 2.00, 2.00, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '色波豹子', '色波'
    public function testSuccPlay152() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,3')
              ->setRules($playId = 152, $odds = [1, 3.88, 3.88], $bet = 1, $betMoney = 1, $playNumber = '红波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,2')
              ->setRules($playId = 152, $odds = [3.01, 3.88, 1], $bet = 1, $betMoney = 1, $playNumber = '蓝波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,9,0')
              ->setRules($playId = 152, $odds = [3.01, 3.88, 3.88], $bet = 1, $betMoney = 100, $playNumber = '红波|绿波|蓝波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(301, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '色波豹子', '色波'
    public function testErrorPlay152() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3')
              ->setRules($playId = 152, $odds = [3.01, 3.88, 3.88], $bet = 1, $betMoney = 1, $playNumber = '绿波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

        // '快捷', '猜和值', '顺子'
    public function testSuccPlay154() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2')
              ->setRules($playId = 154, $odds = [16.67, 1, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '012');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,1')
              ->setRules($playId = 154, $odds = [16.67, 1, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '012');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '猜和值', '顺子'
    public function testErrorPlay154() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2')
              ->setRules($playId = 154, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '123');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

          $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1')
              ->setRules($playId = 154, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '123');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '猜和值', '和值'
    public function testSuccPlay156() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,1')
              ->setRules($playId = 156, $odds = [0, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '猜和值', '和值'
    public function testErrorPlay156() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3')
              ->setRules($playId = 156, $odds = [1, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '猜和值', '和值包三'
    public function testSuccPlay157() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,1')
              ->setRules($playId = 157, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,3,1')
              ->setRules($playId = 157, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '6|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,3')
              ->setRules($playId = 157, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '猜和值', '和值包三'
    public function testErrorPlay157() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3')
              ->setRules($playId = 157, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '27|10|11');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '龙虎斗', '龙虎斗'
    public function testSuccPlay159() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1')
              ->setRules($playId = 159, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '百十龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0')
              ->setRules($playId = 159, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '百个龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3')
              ->setRules($playId = 159, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '百十虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '龙虎斗', '龙虎斗'
    public function testErrorPlay159() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3')
              ->setRules($playId = 159, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '百十龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
}