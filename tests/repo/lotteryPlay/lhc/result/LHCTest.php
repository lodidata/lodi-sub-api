<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/10
 * Time: 15:55
 */

use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class LHCTest extends TestCase {
    const CATE = 51;

    /*
    //猜特码 特码号
    public function testPlay510() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 510, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '06');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,07')
              ->setRules($playId = 510, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '07');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    public function testErrorPlay510() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 510, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '07');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    //猜特码 特码双面
    public function testPlay511() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,25')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,23')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,23')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,26')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,25')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '大单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,23')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '小单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,26')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '大双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,22')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '小双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,26')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '和大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,23')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '和小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,23')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '和单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,24')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '和双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,06')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '尾大');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,43')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '尾小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        //判断开出特码49 和
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,49')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 1, $playNumber = '和小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


    }

    public function testErrorPlay511() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 511, $odds = [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00, 2.00, 2.00, 2.00, 2.00, 2.00, 2.00], $bet = 1, $betMoney = 100, $playNumber = '尾小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    //猜正码 正码号
    public function testPlay512() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('38,49,37,32,43,42,24')
              ->setRules($playId = 512, $odds = [8.00], $bet = 1, $betMoney = 10, $playNumber = '01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(8.16, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 512, $odds = [8.16], $bet = 1, $betMoney = 1, $playNumber = '03');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(8.16, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    public function testErrorPlay512() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 512, $odds = [8.16], $bet = 1, $betMoney = 1, $playNumber = '06');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }
    */

    //十二生肖
    public function testPlay513() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              //->setPeriodCode('02,03,04,31,46,23,49')
              ->setPeriodCode('08,02,03,05,06,07,01')
              ->setRules($playId = 513, $odds = [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,], $bet = 1, $betMoney = 1, $playNumber = '狗', $maxSendPrize = 1000, $openTime = '2019-01-01');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(9.8, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


//        $logic = new Logic;
//        $logic->setCate(self::CATE)
//              ->setPeriodCode('03,04,31,46,23,06,11')
//              ->setRules($playId = 513, $odds = [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,], $bet = 1, $betMoney = 1, $playNumber = '鼠');
//        $logic->isValid(true);
//        $res = $logic->run();
//        $this->assertEquals(12.25, $res[1], print_r($logic->getErrors(), true));
//        $logic->clear();
    }

    /*
    public function testErrorPlay513() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('03,04,31,46,23,06,12')
              ->setRules($playId = 513, $odds = [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,], $bet = 1, $betMoney = 1, $playNumber = '鼠');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '十二生肖', '正码生肖',
    public function testPlay514() {
        $logic = new Logic();

        //2018年
        //狗 01.13.25.37.49
        //兔 08.20.32.44
        //龙 07.19.31.43
        //蛇 06.18.30.42
        //鸡 02.14.26.38

        $logic->setCate(self::CATE)
              ->setPeriodCode('38,49,37,32,43,42,24')
              ->setRules($playId = 514, $odds = ["2.00", "2.00", "2.00", "2.00", "2.00", "2.00", "2.00", "2.00", "2.00", "2.00", "1.92", "2.00"], $bet = 1, $betMoney = 10, $playNumber = '猴|鸡|狗|猪|鼠|牛|虎|兔|龙|蛇|马|羊');

        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(2.92, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,41,23,06')
              ->setRules($playId = 514, $odds = [2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 1.96, 2.04], $bet = 1, $betMoney = 1, $playNumber = '马');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.04, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    public function testErrorPlay514() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 514, $odds = [2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 2.04, 1.96, 2.04], $bet = 1, $betMoney = 1, $playNumber = '马');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    public function testPlay515() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('38,49,37,32,43,42,24')
            //12.24.36.48
              ->setRules($playId = 515, $odds = ["1.76", "1.76", "1.76", "1.76", "1.76", "1.76", "1.76", "1.76", "1.76", "1.76", "2.08", "1.76"], $bet = 1, $betMoney = 10, $playNumber = '猴|鸡|狗|猪|鼠|牛|虎|兔|龙|蛇|马|羊');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(2.12, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


    }

    public function testErrorPlay515() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,24,06')
              ->setRules($playId = 515, $odds = [1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 2.12, 1.8], $bet = 1, $betMoney = 1, $playNumber = '鼠');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    // '快捷', '猜色波', '特码色波',
    public function testPlay516() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,07')
              ->setRules($playId = 516, $odds = [2.08, 3.06, 3.06], $bet = 1, $betMoney = 1, $playNumber = '红波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.08, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,04,31,46,23,06,03')
              ->setRules($playId = 516, $odds = [2.08, 3.06, 3.06], $bet = 1, $betMoney = 1, $playNumber = '蓝波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3.06, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,04,31,46,23,03,06')
              ->setRules($playId = 516, $odds = [2.08, 3.06, 3.06], $bet = 1, $betMoney = 1, $playNumber = '绿波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(3.06, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testErrorPlay516() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,04,31,46,23,06,03')
              ->setRules($playId = 516, $odds = [2.08, 3.06, 3.06], $bet = 1, $betMoney = 1, $playNumber = '绿波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    // '快捷', '猜色波', '特码半波',
    public function testPlay517() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,07')
              ->setRules($playId = 517, $odds = [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,], $bet = 1, $betMoney = 1, $playNumber = '红单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(6, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,04,31,46,23,06,01')
              ->setRules($playId = 517, $odds = [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,], $bet = 1, $betMoney = 1, $playNumber = '红小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4.8, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


    }

    public function testErrorPlay517() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,04,31,46,23,01,06')
              ->setRules($playId = 517, $odds = [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,], $bet = 1, $betMoney = 1, $playNumber = '红小');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '猜色波', '特码三波'
    public function testPlay518() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,29')
              ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 1, $betMoney = 1, $playNumber = '红大单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(16, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,20')
              ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 1, $betMoney = 1, $playNumber = '蓝小双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(12, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,22')
              ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 1, $betMoney = 1, $playNumber = '绿小双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(16, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testErrorPlay518() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,05')
              ->setRules($playId = 518, $odds = [16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,], $bet = 1, $betMoney = 1, $playNumber = '红大单');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    //'快捷', '正特头尾', '特码头数',
    public function testPlay519() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('01,02,03,04,13,25,49')
              ->setRules($playId = 519, $odds = [5.40, 4.90, 4.90, 4.90, 4.90], $bet = 1, $betMoney = 1, $playNumber = '头4');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(5.4, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,16')
              ->setRules($playId = 519, $odds = [5.40, 4.90, 4.90, 4.90, 4.90], $bet = 1, $betMoney = 1, $playNumber = '头1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4.9, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,26')
              ->setRules($playId = 519, $odds = [5.40, 4.90, 4.90, 4.90, 4.90], $bet = 1, $betMoney = 1, $playNumber = '头2');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4.9, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,36')
              ->setRules($playId = 519, $odds = [5.40, 4.90, 4.90, 4.90, 4.90], $bet = 1, $betMoney = 1, $playNumber = '头3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(4.9, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    public function testErrorPlay519() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,11')
              ->setRules($playId = 519, $odds = [5.40, 4.90, 4.90, 4.90, 4.90], $bet = 1, $betMoney = 1, $playNumber = '头0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '正特头尾', '特码尾数',
    public function testPlay520() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,10')
              ->setRules($playId = 520, $odds = [12.25, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80,], $bet = 1, $betMoney = 1, $playNumber = '尾0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(12.25, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,01,04,31,46,23,11')
              ->setRules($playId = 520, $odds = [12.25, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80,], $bet = 1, $betMoney = 1, $playNumber = '尾1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(9.8, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }


    public function testErrorPlay520() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,01,04,31,46,23,10')
              ->setRules($playId = 520, $odds = [12.25, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80, 9.80,], $bet = 1, $betMoney = 1, $playNumber = '尾1');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '正特头尾', '正码尾数',
    public function testPlay521() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,20,30,31,46,23,06')
              ->setRules($playId = 521, $odds = [2.30, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01,], $bet = 1, $betMoney = 1, $playNumber = '尾0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.3, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 521, $odds = [2.30, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01,], $bet = 1, $betMoney = 1, $playNumber = '尾3');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.01, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    public function testErrorPlay521() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,21,31,31,46,23,06')
              ->setRules($playId = 521, $odds = [2.30, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01, 2.01,], $bet = 1, $betMoney = 1, $playNumber = '尾0');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    // '快捷', '正A定位（单号）', '正码一'
    public function testPlay522() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 522, $odds = [48.02], $bet = 1, $betMoney = 10, $playNumber = '01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,03,04,31,46,23,06')
              ->setRules($playId = 522, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '11');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testErrorPlay522() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('12,03,04,31,46,23,06')
              ->setRules($playId = 522, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '11');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '正A定位（单号）', '正码二'
    public function testPlay523() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,01,04,31,46,23,06')
              ->setRules($playId = 523, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '01');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('01,02,04,31,46,23,06')
              ->setRules($playId = 523, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '02');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testErrorPlay523() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('01,02,04,31,46,23,06')
              ->setRules($playId = 523, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '01');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    //'快捷', '正A定位（单号）', '正码三',
    public function testPlay524() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 524, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '04');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,08,31,46,23,06')
              ->setRules($playId = 524, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '08');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(49, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    public function testErrorPlay524() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,08,31,46,23,06')
              ->setRules($playId = 524, $odds = [49.00], $bet = 1, $betMoney = 1, $playNumber = '07');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    //'快捷', '正B定位(双面)', '正码一',
    public function testPlay528() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('38,49,37,32,43,42,24')
              ->setRules($playId = 528, $odds = ["1.96", "2.82", "1.96", "1.96", "1.96", "3.00", "1.96", "1.96", "1.96", "1.96", "3.00", "1.96", "1.96", "3.92", "3.92", "3.92", "3.92"], $bet = 1, $betMoney = 10, $playNumber = '小双|大|小单|单|大双|大单|双|小|和小|和单|和双|和大|尾大|尾小|红波|绿波|蓝波');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('26,03,04,31,46,23,06')
              ->setRules($playId = 528, $odds = [
                  2.0, 2.0, 2.0, 2.0,
                  4.0, 4.0, 4.0, 4.0,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0,
                  2.88, 3.06, 3.06,
              ], $bet = 1, $betMoney = 1, $playNumber = '双');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('19,03,04,31,46,23,06')
              ->setRules($playId = 528, $odds = [
                  2.0, 2.0, 2.0, 2.0,
                  4.0, 4.0, 4.0, 4.0,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0,
                  2.88, 3.06, 3.06,
              ], $bet = 1, $betMoney = 1, $playNumber = '红波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.88, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    public function testErrorPlay528() {

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('06,03,04,31,46,23,06')
              ->setRules($playId = 528, $odds = [
                  2.0, 2.0, 2.0, 2.0,
                  4.0, 4.0, 4.0, 4.0,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0,
                  2.88, 3.06, 3.06,
              ], $bet = 1, $betMoney = 1, $playNumber = '红波');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(0, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }


    //'快捷', 正B定位(双面)', '正码5'
    public function testPlay532() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('01,02,03,04,13,49,25')
              ->setRules($playId = 532, $odds = [
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0,
                  2.0, 2.0, 2.0,
              ], $bet = 1, $betMoney = 1, $playNumber = '大|单|大单|和单|尾小|绿波');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


    }


    //'快捷', 正B定位(双面)', '正码6'
    public function testPlay533() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('45,07,11,19,46,32,37')
              ->setRules($playId = 533, $odds = [
                  2.0, 2.0, 1.98, 1.98,
                  2.0, 2.0, 2.0, 2.0,
                  2.0, 2.0, 3.03, 2.0,
                  2.0, 3.96,
                  2.0, 2.0, 2.0,
              ], $bet = 1, $betMoney = 1, $playNumber = '单|双|和单|尾小');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(1, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();


    }


    //'快捷', '任选中一', '五中一'
    public function testPlay534() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('42,19,29,07,39,09,41')
              ->setRules($playId = 534, $odds = [2.41], $bet = 1, $betMoney = 1, $playNumber = '03|09|10|22|17|11|05');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(2.43, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }


    //'快捷', '任选中一', '五中二'
    public function testPlay535() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,03,04,31,46,23,06')
              ->setRules($playId = 535, $odds = [2.34], $bet = 1, $betMoney = 1, $playNumber = '01|02|11|24|25|26');
        $logic->isValid(true);
        $res = $logic->run();

        $this->assertEquals(2.34, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }


    //'快捷', '任选中一', '八中一'
    public function testPlay536() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('02,09,10,31,46,23,11')
              ->setRules($playId = 536, $odds = [2.33], $bet = 1, $betMoney = 1, $playNumber = '01|02|03|04|05|06|07');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.33, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    //'快捷', '任选不中', '五不中'
    public function testPlay540() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('42,19,29,07,39,09,41')
              ->setRules($playId = 540, $odds = [2.22], $bet = 1, $betMoney = 1, $playNumber = '03|15|21|09|16|10|04|28|05|17');
        $logic->isValid(true);
        $res = $logic->run();
        print_r($res);
        die;
        $this->assertEquals(2.22, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();

    }

    //'快捷', '任选不中', '六不中'
    public function testPlay541() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('10,11,12,31,46,23,13')
              ->setRules($playId = 541, $odds = [2.34], $bet = 1, $betMoney = 1, $playNumber = '01|02|03|04|05|06');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.34, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }

    //'快捷', '任选不中', '七不中'
    public function testPlay542() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('11,13,14,31,46,23,16')
              ->setRules($playId = 542, $odds = [2.33], $bet = 1, $betMoney = 1, $playNumber = '01|02|03|04|05|06|07');
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals(2.33, $res[1], print_r($logic->getErrors(), true));
        $logic->clear();
    }
    */
}