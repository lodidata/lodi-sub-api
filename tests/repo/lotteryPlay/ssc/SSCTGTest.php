<?php
/**
 * 泰国彩票
 */

use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class SSCTGTest extends TestCase
{
    const CATE = 10;

    // '标准', '前三', '直选复式'
    public function testSuccPlay28() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 28, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三', '直选复式'
    public function testErrorPlay28()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 28, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    // '标准', '前二', '直选复式'
    public function testSuccPlay59() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 59, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二', '直选复式'
    public function testErrorPlay59()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 59, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    // '标准', '后二', '直选复式'
    public function testSuccPlay63() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 63, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前二', '直选复式'
    public function testErrorPlay63()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 63, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    // '标准', '前三', '三中一'
    public function testSuccPlay72() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 72, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 72, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 72, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    // '标准', '前三', '三中一'
    public function testErrorPlay72()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 72, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    // '标准', '前三', '前三组选'
    public function testSuccPlay85() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3,4,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
/*
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '3,5,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '4,3,5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '4,5,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5,4,3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5,3,4');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();*/
    }

    // '标准', '前三', '前三组选'
    public function testErrorPlay85()
    {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 85, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1,0,1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    // '标准', '后二', '二中一'
    public function testSuccPlay86() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 86, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 86, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
    // '标准', '后二', '二中一'
    public function testErrorPlay86() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('1,2,3,4,5')
            ->setRules($playId = 86, $odds = [1], $bet = 1, $betMoney = 1, $playNumber = '5');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }
}