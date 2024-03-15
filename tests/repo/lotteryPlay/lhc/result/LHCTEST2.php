<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/10
 * Time: 15:55
 */
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class LHCTest2 extends TestCase {
   const CATE = 51;
    //猜特码 特码号
   public function testPlay528(){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode('04,03,48,28,31,19,27')
            // ['大', '小', '单', '双',
            //      '大单', '小单', '大双', '小双',
            //     '和大', '和小', '和单', '和双',
            //      '尾大', '尾小',
            //      '红波', '蓝波', '绿波',
            // ]
            ->setRules($playId = 528, $odds = [
                1, 1, 10, 1,
                1, 5, 1, 1,
                1, 4, 1, 1,
                1, 1,
                1, 1, 1
            ], $bet = 1, $betMoney = 1, $playNumber = '单|小单|和小');
        $logic->isValid(true);
        // print_r($logic->getErrors());
        // return;
        $res = $logic->run();
        print_r($res);
        $this->assertEquals(2, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

}