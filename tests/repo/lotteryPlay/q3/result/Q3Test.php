<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 快三
 */
class Q3Test extends TestCase {
    const CATE = 5;

    // '标准', '二不同号', '二不同标准'
    public function testSuccPlay601() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,2')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,3,6')
              ->setRules($playId = 601, $odds = [7.2], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3 * 100 * 7.2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二不同标准'
    public function testErrorPlay601() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,1,4')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,4')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,4')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,4')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,4')
              ->setRules($playId = 601, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二不同胆拖'
    public function testSuccPlay602() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2')
              ->setRules($playId = 602, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,2')
              ->setRules($playId = 602, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2')
              ->setRules($playId = 602, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,5,5')
              ->setRules($playId = 602, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,1|3|4|5|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,5,5')
              ->setRules($playId = 602, $odds = [7.2], $bet = 1, $betMoney = 100, $playNumber = '5,1|2|3|4|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(720, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二不同胆拖'
    public function testErrorPlay602() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,1,4')
              ->setRules($playId = 602, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二同号标准'
    public function testSuccPlay603() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,2')
              ->setRules($playId = 603, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,6,2')
              ->setRules($playId = 603, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11|66,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,6,2')
              ->setRules($playId = 603, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11|22|33|44|55|66,1|2|3|4|5|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(30, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二同号标准'
    public function testErrorPlay603() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,1,2')
              ->setRules($playId = 603, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二同号复式'
    public function testSuccPlay604() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,2')
              ->setRules($playId = 604, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11*');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,2')
              ->setRules($playId = 604, $odds = [14.4], $bet = 1, $betMoney = 100, $playNumber = '11*|22*|33*|44*|55*|66*');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1440, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '二不同号', '二同号复式'
    public function testErrorPlay604() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,1,2')
              ->setRules($playId = 604, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11*');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三不同号', '三不同标准'
    public function testSuccPlay605() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 605, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三不同号', '三不同标准'
    public function testErrorPlay605() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 605, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三不同号', '三不同胆拖'
    public function testSuccPlay606() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 606, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三不同号', '三不同胆拖'
    public function testErrorPlay606() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 606, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三同号', '三同号'
    public function testSuccPlay607() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 1], $bet = 1, $betMoney = 1, $playNumber = '三同号通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 607, $odds = [1, 216, 216, 216, 216, 216, 0], $bet = 1, $betMoney = 1, $playNumber = '111');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三同号', '三同号'
    public function testErrorPlay607() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 607, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 1, $playNumber = '222');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三同号', '三连号'
    public function testSuccPlay608() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,3,4')
              ->setRules($playId = 608, $odds = [36, 36, 36, 36, 1], $bet = 1, $betMoney = 1, $playNumber = '三连号通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 608, $odds = [1, 36, 36, 36, 9], $bet = 1, $betMoney = 1, $playNumber = '123');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '三同号', '三连号'
    public function testErrorPlay608() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 608, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 1, $playNumber = '234');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '点数', '点数'
    public function testSuccPlay609() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,5,1')
              ->setRules($playId = 609, $odds = [1, 2.05, 2.05, 2.05, 216, 72, 36, 21.6, 14.4, 10.29, 8.64, 8, 8, 8.64, 10.29, 14.4, 21.6, 36, 72, 216], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '点数', '点数'
    public function testErrorPlay609() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 609, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,6,6')
              ->setRules($playId = 609, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '骰宝', '骰宝'
    public function testSuccPlay610() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,5,1')
              ->setRules($playId = 610, $odds = [1,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 1, $playNumber = '骰宝1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,5,1')
        //       ->setRules($playId = 610, $odds = [0,2.37,2.37,2.37,2.37,2.37,1,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 1, $playNumber = '短牌11');
        // $logic->isValid(true);
        // $res = $logic->run();
        // $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        // $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,1')
              ->setRules($playId = 610, $odds =  [2.37,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 200, $playNumber = '短牌11|短牌22|短牌33|短牌44|短牌55|短牌66');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(13.5 * 200, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,5,6')
              ->setRules($playId = 610, $odds = [0,2.37,2.37,2.37,2.37,2.37,1,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,1], $bet = 1, $betMoney = 1, $playNumber = '长牌56');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,1,4')
        //       ->setRules($playId = 610, $odds =  [2.37,2.37,2.37,2.37,2.37,2.37,13.5,13.5,13.5,13.5,13.5,13.5,9,9,9,9,9,9,9,9,9,9,9,9,9,9,9], $bet = 1, $betMoney = 1, $playNumber = '长牌12|长牌13|长牌14|长牌15|长牌16|长牌23|长牌24|长牌25|长牌26|长牌34|长牌35|长牌36|长牌45|长牌46|长牌56');
        // $logic->isValid(true);
        // $res = $logic->run();
        // $this->assertEquals(9, $res[1], print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // '快捷', '骰宝', '骰宝'
    public function testErrorPlay610() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 610, $odds = [216, 216, 216, 216, 216, 216, 36], $bet = 1, $betMoney = 1, $playNumber = '骰宝2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '骰宝', '骰宝'
    public function testSuccPlay611() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,5,1')
              ->setRules($playId = 611, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '骰宝1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '骰宝', '骰宝'
    public function testErrorPlay611() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 611, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '骰宝2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '骰宝', '短牌'
    public function testSuccPlay612() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,1')
              ->setRules($playId = 612, $odds =  [13.5], $bet = 1, $betMoney = 200, $playNumber = '短牌11|短牌22|短牌33|短牌44|短牌55|短牌66');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(13.5 * 200, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '骰宝', '短牌'
    public function testErrorPlay612() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3')
              ->setRules($playId = 612, $odds = [13.5], $bet = 1, $betMoney = 1, $playNumber = '短牌11|短牌22|短牌33|短牌44|短牌55|短牌66');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '骰宝', '短牌'
    public function testSuccPlay613() {
        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,1,4')
        //       ->setRules($playId = 613, $odds =  [9], $bet = 1, $betMoney = 200, $playNumber = '长牌12|长牌13|长牌14|长牌15|长牌16|长牌23|长牌24|长牌25|长牌26|长牌34|长牌35|长牌36|长牌45|长牌46|长牌56');
        // $logic->isValid(true);
        // $res = $logic->run();
        // $this->assertEquals(9 * 200, $res[1], print_r($logic->getErrors(),true));
        // $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('3,4,5')
              ->setRules($playId = 613, $odds =  [9], $bet = 1, $betMoney = 100, $playNumber = '长牌12|长牌13|长牌14|长牌15|长牌16|长牌23|长牌24|长牌25|长牌26|长牌34|长牌35|长牌36|长牌45|长牌46|长牌56');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(9 * 100 * 3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '骰宝', '短牌'
    public function testErrorPlay613() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,2')
              ->setRules($playId = 613, $odds = [9], $bet = 1, $betMoney = 1, $playNumber = '长牌12|长牌13|长牌14|长牌15|长牌16|长牌23|长牌24|长牌25|长牌26|长牌34|长牌35|长牌36|长牌45|长牌46|长牌56');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

      // '快捷', '骰宝', '大小单双'
    public function testSuccPlay614() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,6,6')
              ->setRules($playId = 614, $odds =  [1], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,6,5')
              ->setRules($playId = 614, $odds =  [1], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1')
              ->setRules($playId = 614, $odds =  [1], $bet = 1, $betMoney = 1, $playNumber = '小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }
}