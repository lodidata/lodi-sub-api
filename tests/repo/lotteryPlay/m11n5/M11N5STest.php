<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * m11n5 传统车位
 */
class M11N5STest extends TestCase {
    const CATE = 24;

    public static function setUpBeforeClass()
    {
        //ini_set('memory_limit', '1024M');
    }

    // 直选复式
    public function testPlay801() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 801, $odds = [990], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 801, $odds = [990], $bet = 1, $betMoney = 100, $playNumber = '1,2,3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 801, $odds = [990], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(990, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('01,02,03,04,05')
              ->setRules($playId = 801, $odds = [990], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(990, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选复式
    public function testPlay802() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 802, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 802, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1|2|3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 802, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(165, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选胆拖
    public function testPlay803() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 803, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 803, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1,2|3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 803, $odds = [165], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(165, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 直选复式
    public function testPlay804() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 804, $odds = [110], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 804, $odds = [110], $bet = 1, $betMoney = 100, $playNumber = '1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 804, $odds = [110], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(110, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选复式
    public function testPlay805() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 805, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 805, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 805, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(55, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 组选胆拖
    public function testPlay806() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 806, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 806, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 806, $odds = [55], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(55, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 1中1复式
    public function testPlay807() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 807, $odds = [2.2], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 807, $odds = [2.2], $bet = 1, $betMoney = 100, $playNumber = '1|2');
        $logic->isValid(true);
        $this->assertEquals(2, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 807, $odds = [2.2], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 2中2复式
    public function testPlay808() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 808, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 808, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1|2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 808, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(55, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 3中3复式
    public function testPlay809() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 809, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 809, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 809, $odds = [5.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(165, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 4中4复式
    public function testPlay810() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 810, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 810, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 810, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(330, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 5中5复式
    public function testPlay811() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 811, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 811, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 811, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(462, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 6中5复式
    public function testPlay812() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 812, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 812, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 812, $odds = [16.5], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(462, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 7中5复式
    public function testPlay813() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 813, $odds = [22], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 813, $odds = [22], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 813, $odds = [22], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(330, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 8中5复式
    public function testPlay814() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 814, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 814, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 814, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(165, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 2中2胆拖
    public function testPlay815() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4');
        $logic->isValid(true);
        $this->assertEquals(3, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 815, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,1|2|3|4');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 3中3胆拖
    public function testPlay816() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 816, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 816, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 816, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2,3');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 4中4胆拖
    public function testPlay817() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 817, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 817, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 817, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3,4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 5中5胆拖
    public function testPlay818() {
        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 818, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        // $logic->isValid(true);
        // $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();


        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 818, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5');
        // $logic->isValid(true);
        // $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 818, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4,5');
        // $logic->isValid(true);
        // $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5')
        //       ->setRules($playId = 818, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,
        //         2|3|4|5|6|7|8|9|10|11');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(210, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 818, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2,
                3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(84, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 6中5胆拖
    public function testPlay819() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 819, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 819, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 819, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5,6');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 819, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(252, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 7中5胆拖
    public function testPlay820() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 820, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 820, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 820, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(210, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 8中5胆拖
    public function testPlay821() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 821, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 821, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7|8');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 821, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(120, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 定位球号
    public function testPlay822() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 822, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 822, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1,,,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 822, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11,1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(55, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 大小单双
    public function testPlay823() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 823, $odds = [1.83, 2.2, 1.83, 2.2], $bet = 1, $betMoney = 100, $playNumber = '大');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 823, $odds = [1.83, 2.2, 1.83, 2.2], $bet = 1, $betMoney = 100, $playNumber = '大,,,,');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 823, $odds = [1.83, 2.2, 1.83, 2.2], $bet = 1, $betMoney = 100, $playNumber = '大|小|单|双,大|小|单|双,大|小|单|双,大|小|单|双,大|小|单|双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(20, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 不定位码
    public function testPlay824() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 824, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 824, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10|11');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(11, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 龙虎斗
    public function testPlay825() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 825, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '一五龙');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 825, $odds = [8.25], $bet = 1, $betMoney = 100, $playNumber = '一五龙|一五虎|二四龙|二四虎');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(4, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 定向单双
    public function testPlay826() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 826, $odds = [77,6.16,2.31,3.08,15.4,462], $bet = 1, $betMoney = 100, $playNumber = '5单0双');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 826, $odds = [77,6.16,2.31,3.08,15.4,462], $bet = 1, $betMoney = 100, $playNumber = '5单0双|4单1双|3单2双|2单3双|1单4双|0单5双');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(6, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 定向中位
    public function testPlay827() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 827, $odds = [16.5, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5], $bet = 1, $betMoney = 100, $playNumber = '3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 827, $odds = [16.5, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5], $bet = 1, $betMoney = 100, $playNumber = '3|4|5|6|7|8|9');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(7, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }
}