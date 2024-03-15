<?php
namespace LotteryPlay\pk10;
use LotteryPlay\BaseValid;

class PK10Valid extends BaseValid {

    // 猜前N
    public static function validS($context, $maxNumberCount = 40) {

       
        $betCount = 0;
        foreach ($context->playNumberList as $val) {
            $betCount += count($val);
        }

        if ($betCount == 0 || $betCount > $maxNumberCount) {
            return $context->stopNext()->setReason('选号不能超过'.$maxNumberCount.'个'.$context->playNumber);
        }

        $context->matrix = matrix2($context->playNumberList);
        // 过滤无用数组
        filterArrayByLength($context->matrix, $context->playNumberLength);
        $context->betCount = count($context->matrix);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }
}