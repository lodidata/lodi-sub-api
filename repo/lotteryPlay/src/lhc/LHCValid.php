<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/5
 * Time: 11:14
 */

namespace LotteryPlay\lhc;
use LotteryPlay\BaseValid;

class LHCValid extends BaseValid
{
    // 任选
    public static function validR($context, $count = 5, $defaultbetCount = 1,$maxNumberCount = 10) {


        $betCount = 0;
        foreach ($context->playNumberList as $val) {
            $betCount += count($val);
        }

        if ($betCount == 0 || $betCount > $maxNumberCount) {
            return $context->stopNext()->setReason('选号不能超过'.$maxNumberCount.'个'.$context->playNumber);
        }

        $context->matrix = count($context->playNumberList[0]) >= $count ? getCombinationToString($context->playNumberList[0], $count) : [];
        $context->betCount = count($context->matrix) * $defaultbetCount;
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
        //连肖玩法计算动态赔率
        if(in_array($context->playId, [551, 552, 553, 554])){
            $context->betOdds = [];//计算的动态赔率
            $sys_odds = array_combine($context->lotteryConfig['standardOddsDesc'], $context->odds);
            foreach ($context->matrix as $mk=>$mval){
                $sx = explode(",", $mval);
                $min_odd = $sys_odds[$sx[0]];
                foreach ($sx as $key=>$val){
                    $tmp_odd = $sys_odds[$val];
                    if($min_odd > $tmp_odd){
                        $min_odd = $tmp_odd;
                    }
                }
                $context->betOdds[$mval] = $min_odd;
            }
        }

    }

}