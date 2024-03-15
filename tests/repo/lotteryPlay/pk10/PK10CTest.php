<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * PK10 传统车位
 */
class PK10CTest extends TestCase {
    const CATE = 39;

    public static function setUpBeforeClass()
    {
        //ini_set('memory_limit', '1024M');
    }

    // 猜第一
    public function testPlay456() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 456, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 456, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(10, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // 猜第二
    public function testPlay457() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 457, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 457, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 457, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(90, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第三
    public function testPlay458() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 458, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 458, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 458, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 458, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(720, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第四
    public function testPlay460() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 460, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 460, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 460, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 460, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(5040, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第五
    public function testPlay461() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 461, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 461, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 461, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 461, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(30240, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第六
    public function testPlay462() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 462, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1,1,2');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 462, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2,1,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 462, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 462, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(151200, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第七
    public function testPlay463() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 463, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1,1,2,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 463, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2,1,1,1');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 463, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6,7');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 463, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(151200, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第八
    public function testPlay464() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 464, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1,1,2,1,3');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 464, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2,1,1,1,3');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 464, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6,7,8');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 464, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(1814400, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }

    // 猜第九
    public function testPlay465() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 465, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,1,2,1,1,2,1,3,4');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 465, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,1,2,1,1,1,3,4');
        $logic->isValid(true);
        $this->assertEquals(0, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
              ->setRules($playId = 465, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1,2,3,4,5,6,7,8,9');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        // $logic = new Logic;
        // $logic->setCate(self::CATE)
        //       ->setPeriodCode('1,2,3,4,5,6,7,8,9,10')
        //       ->setRules($playId = 465, $odds = [10], $bet = 1, $betMoney = 100, $playNumber = '1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10,1|2|3|4|5|6|7|8|9|10');
        // $logic->isValid(true);
        // $logic->run();
        // $this->assertEquals(3628800, $logic->getBetCount(), print_r($logic->getErrors(),true));
        // $logic->clear();
    }
}