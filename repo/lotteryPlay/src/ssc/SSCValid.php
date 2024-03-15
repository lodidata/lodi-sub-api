<?php
namespace LotteryPlay\ssc;
use LotteryPlay\BaseValid;

class SSCValid extends BaseValid {

    public static function validComb60($context) {
        $playList = getCombinationToString($context->playNumberTwoList, 3);
        $context->playNumberList = [];
        foreach ($context->playNumberFirstList as $v1) {
            foreach ($playList as $v2) {
                $lists = explode(',', $v2);
                if ($lists[0] != $v1 && $lists[1] != $v1 && $lists[2] != $v1) {
                    $context->playNumberList[] = array_merge([$v1], $lists);
                }
            }
        }

        $context->betCount = count($context->playNumberList);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validComb30($context) {
        $playList = getCombinationToString($context->playNumberFirstList, 2);
        $context->playNumberList = [];
        foreach ($context->playNumberTwoList as $v1) {
            foreach ($playList as $v2) {
                $lists = explode(',', $v2);
                if ($lists[0] != $v1 && $lists[1] != $v1) {
                    $context->playNumberList[] = array_merge($lists, [$v1]);
                }
            }
        }

        $context->betCount = count($context->playNumberList);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validComb20($context) {
        $playList = getCombinationToString($context->playNumberTwoList, 2);
        $context->playNumberList = [];
        foreach ($context->playNumberFirstList as $v1) {
            foreach ($playList as $v2) {
                $lists = explode(',', $v2);
                if ($lists[0] != $v1 && $lists[1] != $v1) {
                    $context->playNumberList[] = array_merge([$v1], $lists);
                }
            }
        }

        $context->betCount = count($context->playNumberList);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validComb10($context) {
        $context->playNumberList = [];
        foreach ($context->playNumberFirstList as $v1) {
            foreach ($context->playNumberTwoList as $v2) {
                if ($v1 != $v2) {
                    $context->playNumberList[] = [$v1, $v2];
                }
            }
        }

        $context->betCount = count($context->playNumberList);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validBaccarat($context) {
        $sets = ['庄', '庄对子', '庄豹子', '庄天王', '闲', '闲对子', '闲豹子', '闲天王'];
        $lists = [];
        foreach ($context->playNumberList as $v) {
            if (in_array($v, $sets)) {
                $lists[] = $v;
            }
        }
        $context->playNumberList = $lists;
        unset($lists);

        $context->betCount = count($context->playNumberList);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validYffs($context) {
        $context->betCount = count($context->playNumberList[0]);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }
}