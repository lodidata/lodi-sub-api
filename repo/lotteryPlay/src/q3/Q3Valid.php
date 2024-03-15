<?php
namespace LotteryPlay\q3;
use LotteryPlay\BaseValid;

class Q3Valid extends BaseValid {

    
    // public static function validSb($context) {
    //     $context->matrix = matrix2($context->playNumberList);
    //     // 过滤无用数组
    //     filterArrayByLength($context->matrix, $context->playNumberLength);
    //     $context->betCount = count($context->matrix);
    //     if ($context->betCount == 0) {
    //         $context->stopNext()->setReason('注单计算异常');
    //     }
    // }

    public static function validSEbh($context) {
        $valDefine = [11 => 1, 22 => 2, 33 => 3, 44 => 4, 55 => 5, 66 => 6];
        foreach ($context->playNumberList[0] as $key => $value) {
            $context->playNumberList[0][$key] = $valDefine[$value];
        }

        $context->matrix = matrix2($context->playNumberList);
        // 过滤无用数组
        filterArrayByLength($context->matrix, $context->playNumberLength);
        $context->betCount = count($context->matrix);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validSEbh2($context) {
        $valDefine = ['11*' => 1, '22*' => 2, '33*' => 3, '44*' => 4, '55*' => 5, '66*' => 6];
        foreach ($context->playNumberList[0] as $key => $value) {
            $context->playNumberList[0][$key] = $valDefine[$value];
        }
        $context->betCount = count($context->playNumberList[0]);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

}