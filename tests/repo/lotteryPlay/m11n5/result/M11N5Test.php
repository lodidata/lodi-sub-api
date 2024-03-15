<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * m11n5
 */
class M11N5Test extends TestCase {
    const CATE = 24;

    // '标准', '前三码', '直选复式'
    public function testSuccPlay801() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 801, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三码', '直选复式'
    public function testErrorPlay801() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 801, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // '标准', '前三码', '组选复式'
    public function testSuccPlay802() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 802, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三码', '组选复式'
    public function testErrorPlay802() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 802, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '9|3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三码', '组选胆拖'
    public function testSuccPlay803() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 803, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三码', '组选胆拖'
    public function testErrorPlay803() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 803, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '9,3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '直选复式'
    public function testSuccPlay804() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 804, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '直选复式'
    public function testErrorPlay804() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 804, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '组选复式'
    public function testSuccPlay805() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 805, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '组选复式'
    public function testErrorPlay805() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 805, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '9|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '组选胆拖'
    public function testSuccPlay806() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 806, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二码', '组选胆拖'
    public function testErrorPlay806() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 806, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选复式', '1中1复式'    
    public function testSuccPlay807() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 807, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选复式', '1中1复式'
    public function testErrorPlay807() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 807, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选复式', '2中2复式'    
    public function testSuccPlay808() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 808, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选复式', '2中2复式'
    public function testErrorPlay808() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 808, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选胆拖', '2中2胆拖'
    public function testSuccPlay815() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '任选胆拖', '2中2胆拖'
    public function testErrorPlay815() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,6');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位', '定位球号'
    public function testSuccPlay822() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 822, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(5, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位', '定位球号'
    public function testErrorPlay822() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 822, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5,,,,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位', '大小单双'
    public function testSuccPlay823() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,4,5')
              ->setRules($playId = 823, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '大,小,单,双,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4+3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,4,7,1,6')
              ->setRules($playId = 823, $odds = [1.83, 2.2, 1.83, 2.2], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,小|大|单|双,大|小|单|双,小|大|单|双,大|小|单|双');

        // 小|双,小|双,大|单,小|单,大|双
        // 双 * 3   
        // 小 * 3
        // 单 * 2
        // 大 * 2
        // （3 * 2.2 + 2.2 * 3 + 1.83 * 2 + 2 * 1.83） * 100
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals((3 * 2.2 + 2.2 * 3 + 1.83 * 2 + 2 * 1.83) * 100, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位', '大小单双'
    public function testErrorPlay823() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 823, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '大,,,,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '不定位码'
    public function testSuccPlay824() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,4,5')
              ->setRules($playId = 824, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '11');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '不定位码'
    public function testErrorPlay824() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,4,5')
              ->setRules($playId = 824, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '龙虎斗'
    public function testSuccPlay825() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,4,5')
              ->setRules($playId = 825, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '一五龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,4,7,1,6')
              ->setRules($playId = 825, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '一五龙|一五虎|二四龙|二四虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2 * 2 * 100, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('03,05,01,08,04')
              ->setRules($playId = 825, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '一五龙|一五虎,二四龙|二四虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2 * 2 * 100, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '龙虎斗'
    public function testErrorPlay825() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,4,5')
              ->setRules($playId = 825, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '一五虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '定向单双'
    public function testSuccPlay826() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,7,3,9,5')
              ->setRules($playId = 826, $odds = [1,6.16,2.31,3.08,15.4,462], $bet = 1, $betMoney = 1, $playNumber = '5单0双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,7,3,9,4')
              ->setRules($playId = 826, $odds = [0,1,0,3.08,15.4,462], $bet = 1, $betMoney = 1, $playNumber = '4单1双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,9,4')
              ->setRules($playId = 826, $odds = [0,0,1,3.08,15.4,462], $bet = 1, $betMoney = 1, $playNumber = '3单2双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,3,6,4')
              ->setRules($playId = 826, $odds = [0,0,0,1,15.4,462], $bet = 1, $betMoney = 1, $playNumber = '2单3双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,2,8,6,4')
              ->setRules($playId = 826, $odds = [0,0,2.31,0,1,0], $bet = 1, $betMoney = 1, $playNumber = '1单4双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,2,8,6,4')
              ->setRules($playId = 826, $odds = [0,1,2.31,3.08,0,1], $bet = 1, $betMoney = 1, $playNumber = '0单5双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '趣味玩法', '定向单双'
    public function testErrorPlay826() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,7,3,9,4')
              ->setRules($playId = 826, $odds = [77,6.16,2.31,3.08,15.4,462], $bet = 1, $betMoney = 1, $playNumber = '5单0双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // '标准', '趣味玩法', '定向中位'
    public function testSuccPlay827() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 827, $odds = [1, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5], $bet = 1, $betMoney = 1, $playNumber = '3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,4,7,1,6')
              ->setRules($playId = 827, $odds = [16.5, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5], $bet = 1, $betMoney = 100, $playNumber = '3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(513, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '趣味玩法', '定向中位'
    public function testErrorPlay827() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 827, $odds = [16.5, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5], $bet = 1, $betMoney = 1, $playNumber = '4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
}