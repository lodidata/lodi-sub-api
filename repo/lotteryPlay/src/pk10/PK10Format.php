<?php
namespace LotteryPlay\pk10;
use LotteryPlay\BaseFormat;

class PK10Format extends BaseFormat {

    public static function periodCodeFormat2($context, $playNumberLength = 10, $range = [1, 2, 3, 4 ,5 , 6, 7, 8, 9, 10]) {
        $code = $context->periodCode;
        if (empty($code)) {
            return $context->stopNext()->setReason('开奖号码不能为空');
        }
        
        $context->periodCodeList = explode(',', $context->periodCode);
        if (count($context->periodCodeList) != $playNumberLength) {
            return $context->stopNext()->setReason('开奖号码格式不正确');
        }

        $temp = [];
        foreach ($context->periodCodeList as $code) {
            if (!in_array($code, $range) || in_array($code, $temp)) {
                return $context->stopNext()->setReason('开奖号码内容不正确');
            }

            $temp[] = $code;
        }
    }
}