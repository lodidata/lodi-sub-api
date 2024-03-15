<?php
namespace LotteryPlay\m11n5;
use LotteryPlay\BaseValid;

class M11N5Valid extends BaseValid {

    public static function validMn2($context, $count = 4, $defaultbetCount = 1, $dn = 1) {
        $context->matrix = count($context->playNumberList[0]) >= $count ? getCombinationToString($context->playNumberList[0], $count) : [];
        $context->betCount = count($context->matrix) * $defaultbetCount / $dn;
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }
}