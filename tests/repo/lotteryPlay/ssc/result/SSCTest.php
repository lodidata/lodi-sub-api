<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class SSCTest extends TestCase {
    const CATE = 10;

    // 直选复式
    public function testSuccPlay1() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 1, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选复式
    public function testErrorPlay1() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 1, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选组合
    public function testSuccPlay2() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '0,1,2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(5 + 4 + 3 + 2 + 1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4 + 3 + 2 + 1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3 + 2 + 1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2 + 1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,2,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,2,0,3,5')
              ->setRules($playId = 2, $odds = [5, 4 ,3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '3,3,3,3,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 直选组合
    public function testErrorPlay2() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 2, $odds = [10], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 通选复式
    public function testSuccPlay3() {
        // 中五星
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // 前三星
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,0,0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // 后三星
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '0,0,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // 前二星
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,0,0,0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        // 后二星
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '0,0,0,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [100000, 1000, 100], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        // print_r($logic->getWinBetCount());
        $this->assertEquals(47710000, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
        
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,8,9,7,8')
              ->setRules($playId = 3, $odds = ["100000.00", "1000.00", "100.00"], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9,0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        // print_r($logic->getWinBetCount());
        $this->assertEquals(47710000, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 通选复式
    public function testErrorPlay3() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,0,0,0,0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

                $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 3, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '0,0,0,0,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星直选', '通选复式'
    public function testSuccPlay4() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 4, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5|4|3|2|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星直选', '通选复式'
    public function testErrorPlay4() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 4, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|2|3|4|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选60'
    public function testSuccPlay5() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,1,0')
              ->setRules($playId = 5, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,1,0')
              ->setRules($playId = 5, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|5,1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选60'
    public function testErrorPlay5() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 5, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选30'
    public function testSuccPlay6() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,3,1,0')
              ->setRules($playId = 6, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,3,1,0')
              ->setRules($playId = 6, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1|5,3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选30'
    public function testErrorPlay6() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 6, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选20'
    public function testSuccPlay7() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,3,2')
              ->setRules($playId = 7, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,3,2')
              ->setRules($playId = 7, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1,2|3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选20'
    public function testErrorPlay7() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 7, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选10'
    public function testSuccPlay8() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,2,2')
              ->setRules($playId = 8, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,2,2')
              ->setRules($playId = 8, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|3,2|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选10'
    public function testErrorPlay8() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,2,2')
              ->setRules($playId = 8, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选5'
    public function testSuccPlay9() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,2')
              ->setRules($playId = 9, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,2')
              ->setRules($playId = 9, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|3,2|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '五星组选', '组选5'
    public function testErrorPlay9() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,2')
              ->setRules($playId = 9, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,2,2,2')
              ->setRules($playId = 9, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,1')
              ->setRules($playId = 9, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // 百家乐
    public function testSuccPlay10() {
        // [万位+千位]>[十位+个位]即中奖.奖金N元/注.
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,4,3,1,2')
              ->setRules($playId = 10, $odds = [1,0,0,0,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,5,3,1,2')
              ->setRules($playId = 10, $odds = [0,1,0,0,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄对子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,5,5,1,2')
              ->setRules($playId = 10, $odds = [0,0,1,0,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄豹子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,4,5,1,2')
              ->setRules($playId = 10, $odds = [0,0,0,1,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,5,5,1,2')
              ->setRules($playId = 10, $odds = [0,0,0,1,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,4,5,1,2')
              ->setRules($playId = 10, $odds = [0,0,0,1,0,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '庄天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,4,3,5,5')
              ->setRules($playId = 10, $odds = [0,0,0,0,1,0,0,0], $bet = 1, $betMoney = 1, $playNumber = '闲');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,5,3,1,1')
              ->setRules($playId = 10, $odds = [0,0,0,0,0,1,0,0], $bet = 1, $betMoney = 1, $playNumber = '闲对子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,5,5,5')
              ->setRules($playId = 10, $odds = [0,0,0,0,0,0,1,0], $bet = 1, $betMoney = 1, $playNumber = '闲豹子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,4,5,4,4')
              ->setRules($playId = 10, $odds = [0,0,0,0,0,0,0,1], $bet = 1, $betMoney = 1, $playNumber = '闲天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,5,5,4,5')
              ->setRules($playId = 10, $odds = [0,0,0,0,0,0,0,1], $bet = 1, $betMoney = 1, $playNumber = '闲天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,4,5,4,4')
              ->setRules($playId = 10, $odds = [0,0,0,0,0,0,0,1], $bet = 1, $betMoney = 1, $playNumber = '闲天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('8,8,7,1,7')
              ->setRules($playId = 10, $odds = [2.1,10,100,5.2,2.1,10,100,5.2], $bet = 1, $betMoney = 100, $playNumber = '庄|庄对子|庄豹子|庄天王,闲天王|闲豹子|闲对子|闲');
        $logic->isValid(true);
        $res = $logic->run();
        // 中 庄 庄对子 闲天王
        $this->assertEquals((2.1 + 10 + 5.2) * 100, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 百家乐
    public function testErrorPlay10() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,3,1,2')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '庄');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,5,3,1,2')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '庄对子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,1,5,1,2')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '庄豹子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,3,5,1,2')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '庄天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,4,3,1,2')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '闲');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('5,5,3,0,1')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '闲对子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,5,1,5')
              ->setRules($playId = 10, $odds = [1,1,1,1,1,1,1,1], $bet = 1, $betMoney = 1, $playNumber = '闲豹子');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,4,5,3,4')
              ->setRules($playId = 10, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '闲天王');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 一帆风顺
    public function testSuccPlay11() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 11, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 11, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 11, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 11, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 一帆风顺
    public function testErrorPlay11() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 11, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 好事成双
    public function testSuccPlay12() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 12, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,3,4,5')
              ->setRules($playId = 12, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,1,2,5')
              ->setRules($playId = 12, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 好事成双
    public function testErrorPlay12() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 12, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三星报喜
    public function testSuccPlay13() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 13, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,2,4,5')
              ->setRules($playId = 13, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,2,5')
              ->setRules($playId = 13, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 三星报喜
    public function testErrorPlay13() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 13, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 四季发财
    public function testSuccPlay14() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,1,5')
              ->setRules($playId = 14, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,2,2,2,5')
              ->setRules($playId = 14, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,1,5')
              ->setRules($playId = 14, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 四季发财
    public function testErrorPlay14() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 14, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四复式
    public function testSuccPlay15() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1,1,5')
              ->setRules($playId = 15, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,2,2,5')
              ->setRules($playId = 15, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,2,2,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 15, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|1,2,2,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四复式
    public function testErrorPlay15() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 15, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组合
    public function testSuccPlay16() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4+3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,2,2,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,2,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,2,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,5|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组合
    public function testErrorPlay16() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 16, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四复式
    public function testSuccPlay17() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1,1,5')
              ->setRules($playId = 17, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,2,2,5')
              ->setRules($playId = 17, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,2,2,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 17, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|1,2,2,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四复式
    public function testErrorPlay17() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 17, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,1,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组合
    public function testSuccPlay18() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4+3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,2,2,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,2,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,2,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,2,2,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1,2|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组合
    public function testErrorPlay18() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,4,5')
              ->setRules($playId = 18, $odds = [4,3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选24
    public function testSuccPlay19() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 19, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|3|4|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选24
    public function testErrorPlay19() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 19, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|3|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选12
    public function testSuccPlay20() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,2')
              ->setRules($playId = 20, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选12
    public function testErrorPlay20() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,2')
              ->setRules($playId = 20, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选6
    public function testSuccPlay22() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,1')
              ->setRules($playId = 22, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选6
    public function testErrorPlay22() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,2')
              ->setRules($playId = 22, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选4
    public function testSuccPlay23() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,0,1')
              ->setRules($playId = 23, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 后四组选4
    public function testErrorPlay23() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,0,2')
              ->setRules($playId = 23, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选24
    public function testSuccPlay24() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 24, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|3|4|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选24
    public function testErrorPlay24() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 24, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|3|4|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选12
    public function testSuccPlay25() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,2,2')
              ->setRules($playId = 25, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选12
    public function testErrorPlay25() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,2')
              ->setRules($playId = 25, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选6
    public function testSuccPlay26() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,1')
              ->setRules($playId = 26, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选6
    public function testErrorPlay26() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,0,1,2')
              ->setRules($playId = 26, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选4
    public function testSuccPlay27() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 27, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 前四组选4
    public function testErrorPlay27() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 27, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选复式'
    public function testSuccPlay28() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 28, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选复式'
    public function testErrorPlay28() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 28, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选组合'
    public function testSuccPlay29() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 29, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '0,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3+2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 29, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,1,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2+1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 29, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选组合'
    public function testErrorPlay29() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 29, $odds = [3,2,1], $bet = 1, $betMoney = 1, $playNumber = '1,0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选和值'
    public function testSuccPlay31() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,0')
              ->setRules($playId = 31, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,4,5')
              ->setRules($playId = 31, $odds = [0, 37, 37, 37, 37, 37, 37, 37, 37, 1, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选和值'
    public function testErrorPlay31() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 31, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选跨度'
    public function testSuccPlay32() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,0')
              ->setRules($playId = 32, $odds = [1,18.52,10.42,7.94,6.94,6.67,6.94,7.94,10.42,18.52], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,4,5')
              ->setRules($playId = 32, $odds = [100,18.52,10.42,7.94,111,1,6.94,7.94,10.42,18.52], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,4,0,5')
              ->setRules($playId = 32, $odds = [100,18.52,10.42,7.94,11,1,6.94,7.94,10.42,18.52], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '直选跨度'
    public function testErrorPlay32() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 32, $odds = [100,18.52,10.42,7.94,6.94,6.67,6.94,7.94,10.42,18.52], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组三包号'
    public function testSuccPlay33() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,1')
              ->setRules($playId = 33, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,1,1')
              ->setRules($playId = 33, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组三包号'
    public function testErrorPlay33() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 33, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组三直选'
    public function testSuccPlay34() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,1')
              ->setRules($playId = 34, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组三直选'
    public function testErrorPlay34() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 34, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组六包号'
    public function testSuccPlay35() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,2')
              ->setRules($playId = 35, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1|2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,2')
              ->setRules($playId = 35, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|1|0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,8,9')
              ->setRules($playId = 35, $odds = [164.17], $bet = 1, $betMoney = 240, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(328.34 * 100, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '组六包号'
    public function testErrorPlay35() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,2')
              ->setRules($playId = 35, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1|3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三豹子'
    public function testSuccPlay36() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,1,1')
              ->setRules($playId = 36, $odds = [1, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 1, $playNumber = '豹子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,0')
              ->setRules($playId = 36, $odds = [100, 1, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 1, $playNumber = '000');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三豹子'
    public function testErrorPlay36() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 36, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 1, $playNumber = '豹子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 36, $odds = [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000], $bet = 1, $betMoney = 1, $playNumber = '000');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三顺子'
    public function testSuccPlay37() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,2,3')
              ->setRules($playId = 37, $odds = [1, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '顺子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,2')
              ->setRules($playId = 37, $odds = [16.67, 1, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '012');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三顺子'
    public function testErrorPlay37() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 37, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '顺子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 37, $odds = [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66], $bet = 1, $betMoney = 1, $playNumber = '012');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三对子'
    public function testSuccPlay38() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,1,0')
              ->setRules($playId = 38, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,0,0')
              ->setRules($playId = 38, $odds = [3.7, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '00');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,1')
              ->setRules($playId = 38, $odds = [3.7, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '00');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,0')
              ->setRules($playId = 38, $odds = [3.7, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '00');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,0')
              ->setRules($playId = 38, $odds = [1, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,9,9,0')
              ->setRules($playId = 38, $odds = [1, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,9')
              ->setRules($playId = 38, $odds = [1, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,9,9,9')
              ->setRules($playId = 38, $odds = [1, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后三', '后三对子'
    public function testErrorPlay38() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,1,2')
              ->setRules($playId = 38, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,0,2')
              ->setRules($playId = 36, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '00');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,1,1,1')
              ->setRules($playId = 38, $odds = [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37], $bet = 1, $betMoney = 1, $playNumber = '对子通选');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }


    // '标准', '中三', '直选复式'
    public function testSuccPlay39() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 39, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '中三', '直选复式'
    public function testErrorPlay39() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 39, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,3,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '中三', '直选组合'
    public function testSuccPlay40() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,2,3,4')
              ->setRules($playId = 40, $odds = [3 ,2 ,1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3 + 2 + 1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '中三', '直选组合'
    public function testErrorPlay40() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 40, $odds = [10], $bet = 1, $betMoney = 1, $playNumber = '0,2,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '中三', '直选和值'
    public function testSuccPlay41() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,1,0,0')
              ->setRules($playId = 41, $odds = [1111, 1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '中三', '直选和值'
    public function testErrorPlay41() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 41, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三', '直选和值'
    public function testSuccPlay51() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,0')
              ->setRules($playId = 51, $odds = [1111, 1111, 1, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三', '直选和值'
    public function testErrorPlay51() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 51, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '组选复式'
    public function testSuccPlay59() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,0')
              ->setRules($playId = 59, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '组选复式'
    public function testErrorPlay59() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 59, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '直选和值'
    public function testSuccPlay60() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,0')
              ->setRules($playId = 60, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '直选和值'
    public function testErrorPlay60() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 60, $odds = [1, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '直选跨度'
    public function testSuccPlay61() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,0')
              ->setRules($playId = 61, $odds = [1,5.55,6.25,7.14,8.33,10,12.5,16.67,25,50], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1,0,0')
              ->setRules($playId = 61, $odds = [1,5.55,6.25,7.14,8.33,10,12.5,16.67,25,50], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '直选跨度'
    public function testErrorPlay61() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 61, $odds = [10,5.55,6.25,7.14,8.33,10,12.5,16.67,25,50], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '组选复式'
    public function testSuccPlay62() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,1')
              ->setRules($playId = 62, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '后二', '组选复式'
    public function testErrorPlay62() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,2')
              ->setRules($playId = 62, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '两面', '龙虎斗'
    public function testSuccPlay67() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,0,1,0,1')
              ->setRules($playId = 67, $odds = [
                1,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10
            ], $bet = 1, $betMoney = 1, $playNumber = '万千龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1,0,1')
              ->setRules($playId = 67, $odds = [
                1,1,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10
            ], $bet = 1, $betMoney = 1, $playNumber = '万千虎');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,1,0,1')
              ->setRules($playId = 67, $odds = [
                0,0,1,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10
            ], $bet = 1, $betMoney = 1, $playNumber = '万千合');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,0,0,1')
              ->setRules($playId = 67, $odds = [
                0,0,0,
                1,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10
            ], $bet = 1, $betMoney = 1, $playNumber = '万百龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '两面', '龙虎斗'
    public function testErrorPlay67() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,0,0,2')
              ->setRules($playId = 67, $odds = [
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10,
                2.2,2.2,10
            ], $bet = 1, $betMoney = 1, $playNumber = '万千龙');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '两面', '五字和'
    public function testSuccPlay68() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('9,9,9,9,9')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '总数大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,0')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '总数小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,1')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '总数单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('2,0,0,0,0')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '总数双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,9')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '和尾数大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,0')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '和尾数小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,1')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '和尾数质');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,0,0,0,0')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '和尾数合');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '两面', '五字和'
    public function testErrorPlay68() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,0,0,0,0')
              ->setRules($playId = 68, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '总数大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位胆', '球号'
    public function testSuccPlay69() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 69, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(5, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位胆', '球号'
    public function testErrorPlay69() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 69, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,1,1,1,1')
              ->setRules($playId = 69, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后三一码'
    public function testSuccPlay70() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 70, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,5,5')
              ->setRules($playId = 70, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,5,5')
              ->setRules($playId = 70, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '定位胆', '球号'
    public function testErrorPlay70() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 70, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '中三一码'
    public function testSuccPlay71() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 71, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '中三一码'
    public function testErrorPlay71() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 71, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后三二码'
    public function testSuccPlay73() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 73, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 73, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后三二码'
    public function testErrorPlay73() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 73, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后四一码'
    public function testSuccPlay76() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 76, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|3|4|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后四一码'
    public function testErrorPlay76() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 76, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后四二码'
    public function testSuccPlay77() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 77, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2|4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 77, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('4,7,7,2,5')
              ->setRules($playId = 77, $odds = [10.20], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3060, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '后四二码'
    public function testErrorPlay77() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 77, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '五星二码'
    public function testSuccPlay78() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 78, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();


        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('9,5,1,0,6')
        //       ->setRules($playId = 78, $odds = [6.81], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        // $logic->isValid(true);
        // $res = $logic->run();
        // $this->assertEquals(22980, $res[1], print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // '快捷', '不定位', '五星二码'
    public function testErrorPlay78() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 78, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '6|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '五星三码'
    public function testSuccPlay79() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 79, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1|2|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('9,5,1,0,6')
              ->setRules($playId = 79, $odds = [22.98], $bet = 1, $betMoney = 100, $playNumber = '0|1|2|3|4|5|6|7|8|9');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(22980, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '五星三码'
    public function testErrorPlay79() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('0,2,3,4,5')
              ->setRules($playId = 79, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0|6|5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '不定位', '单球'
    public function testSuccPlay80() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,9,5')
              ->setRules($playId = 80, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '小,双,单,大,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '不定位', '单球'
    public function testErrorPlay80() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,1,4,4,5')
              ->setRules($playId = 80, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '小,双,单,大,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('3,2,5,9,8')
              ->setRules($playId = 80, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '|小,|小|大,,,');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $this->assertEquals(3, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


    }

    // '快捷', '大小单双', '后二'
    public function testSuccPlay81() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,9,9')
              ->setRules($playId = 81, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '单,大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '大小单双', '后二'
    public function testErrorPlay81() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,1,4,4,4')
              ->setRules($playId = 81, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '单,大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '快捷', '大小单双', '后三'
    public function testSuccPlay83() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,9,2')
              ->setRules($playId = 83, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '单,大,小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

    }

    // '快捷', '大小单双', '后三'
    public function testErrorPlay83() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('6,1,4,4,5')
              ->setRules($playId = 83, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '单,大,双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
}