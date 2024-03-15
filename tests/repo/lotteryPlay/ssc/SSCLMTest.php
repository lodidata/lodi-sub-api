<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;
/**
 * 分分彩 两面
 */
class SSCLMTest extends TestCase {
    const CATE = 10;

    // 龙虎斗
    public function testPlay67() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
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
            ], $bet = 1, $betMoney = 100, $playNumber = '万千虎');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
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
            ], $bet = 1, $betMoney = 100, $playNumber = '万千龙|万千虎|万千合|万百龙|万百虎|万百合|万十龙|万十虎|万十合|万个龙|万个虎|万个合|千百龙|千百虎|千百合|千十龙|千十虎|千十合|千个龙|千个虎|千个合|百十龙|百十虎|百十合|百个龙|百个虎|百个合|十个龙|十个虎|十个合');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(30, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
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
            ], $bet = 1, $betMoney = 100, $playNumber = '万千龙|万千虎,万千合,万百龙,万百虎,万百合,万十龙,万十虎,万十合,万个龙,万个虎,万个合,千百龙,千百虎,千百合,千十龙,千十虎,千十合,千个龙,千个虎,千个合,百十龙,百十虎,百十合,百个龙,百个虎,百个合,十个龙,十个虎,十个合');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(30, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    } 

    // 五字和
    public function testPlay68() {
        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,2,3,4,5')
              ->setRules($playId = 68, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '总数大');
        $logic->isValid(true);
        $this->assertEquals(1, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();

        $logic = new Logic;
        $logic->setCate(self::CATE)
              ->setPeriodCode('1,1,3,4,5')
              ->setRules($playId = 68, $odds = [2], $bet = 1, $betMoney = 100, $playNumber = '总数大|总数小|总数单|总数双|和尾数大|和尾数小|和尾数质|和尾数合');
        $logic->isValid(true);
        $logic->run();
        $this->assertEquals(8, $logic->getBetCount(), print_r($logic->getErrors(),true));
        $logic->clear();
    } 
}