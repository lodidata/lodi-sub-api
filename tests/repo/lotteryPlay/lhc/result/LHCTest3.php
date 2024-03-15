<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/10
 * Time: 15:55
 */
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
require __DIR__.'/CsvReader.php';
class LHCTest extends TestCase
{
    const CATE = 51;

    /**
     * @dataProvider additionProvider
    */
    public function testPlay($playId1,$periodCode1,$odds1,$bet1,$betMoney1,$playNumber1,$expected1,$comment1){
        $logic = new Logic;
        $logic->setCate(self::CATE)
            ->setPeriodCode("$periodCode1")
            ->setRules($playId = $playId1, $odds = explode(",",$odds1), $bet = $bet1, $betMoney = $betMoney1, $playNumber = "$playNumber1");
        $logic->isValid(true);
        $res = $logic->run();
        $this->assertEquals($expected1, $res[1], print_r($logic->getErrors(),true));
        $logic->clear();
    }

    public function additionProvider(){
        return new CsvReader(__DIR__.'/data.csv');
    }
}                                                                                                                                                                                                                                                                              