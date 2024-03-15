<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/5
 * Time: 11:13
 */

namespace LotteryPlay\lhc;
use LotteryPlay\BaseFormat;

class LHCFormat extends BaseFormat
{

    public static function periodCodeFormat3($context, $playNumberLength = 7, $range = [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"]) {
        $code = $context->periodCode;
        if (empty($code)) {
            return $context->stopNext()->setReason('开奖号码不能为空');
        }

        $context->periodCodeList = explode(',', $context->periodCode);
        if (count($context->periodCodeList) != $playNumberLength) {
            return $context->stopNext()->setReason('开奖号码格式不正确');
        }
        
        $context->periodCodeList = array_map('intval', $context->periodCodeList);
        $context->periodCodeList = array_map(function ($code) {
            return str_pad($code, 2, '0', STR_PAD_LEFT);
        }, $context->periodCodeList);

        $temp = [];
        foreach ($context->periodCodeList as $code) {
            if (!in_array($code, $range) || in_array($code, $temp)) {
                return $context->stopNext()->setReason('开奖号码内容不正确');
            }

            $temp[] = $code;
        }
    }
}