<?php 
namespace LotteryPlay;

abstract class BaseValid {

    public static function valid($context, $defaultbetCount = 1) {
        $context->defaultbetCount = $defaultbetCount;

        $context->playNumberLength = isset($context->playNumberLength) ? $context->playNumberLength : 1;
        if (!empty($context->playNumberList)) {
            $context->betCount = $context->defaultbetCount;
            for ($i = 0;$i < $context->playNumberLength; $i++) {
                $context->betCount *= count($context->playNumberList[$i]); 
            }
        } else {
            $context->betCount = 0;
        }

        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }
    
    public static function validMn($context, $count = 4, $defaultbetCount = 1) {
        $context->matrix = count($context->playNumberList[0]) >= $count ? getCombinationToString($context->playNumberList[0], $count) : [];
        $context->betCount = count($context->matrix) * $defaultbetCount;
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    /**
     * 不定位、定位胆
     * @param  [type]  $context         [description]
     * @param  integer $defaultbetCount [description]
     * @return [type]                   [description]
     */
    public static function validDwd($context, $defaultbetCount = 0) {
        $context->defaultbetCount = $defaultbetCount;
        $context->betCount = $context->defaultbetCount;
        if (!empty($context->playNumberList)) {
            $context->betCount = $context->defaultbetCount;
            for ($i = 0;$i < count($context->playNumberList); $i++) {
                $c = 0;
                foreach ($context->playNumberList[$i] as $v) {
                    if ($v === '') {
                        continue;
                    }
                    $c++;
                }
                $context->betCount += $c; 
            }
        } else {
            $context->betCount = 0;
        }

        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    public static function validSb($context) {
        $context->matrix = matrix2($context->playNumberList);
        // 过滤无用数组
        filterArrayByLength($context->matrix, $context->playNumberLength);
        $context->betCount = count($context->matrix);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    /**
     * 复式
     * @param  [type]  $context [description]
     * @param  integer $count   [description]
     * @return [type]           [description]
     */
    public static function validSb3($context, $count = 3) {
        $lists = array_unique(array_merge($context->playNumberList[0], $context->playNumberList[1]));
        $context->matrix = count($lists) >= $count ? getCombinationToString($lists, $count) : [];

        // 过滤不带胆的数据
        foreach ($context->matrix as $k => $v) {
            $temp = explode(',', $v);
            $on = false;
            foreach ($context->playNumberList[0] as $v2) {
                if (in_array($v2, $temp)) {
                    $on = true;
                }
            }

            if (!$on) {
                unset($context->matrix[$k]);
            }
        }

        $context->betCount = count($context->matrix);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }

    /**
     * 胆拖
     * @param  [type]  $context [description]
     * @param  integer $count   [description]
     * @return [type]           [description]
     */
    public static function validSb2($context, $count = 3) {
        $lists = array_unique(array_merge($context->playNumberList[0], $context->playNumberList[1]));
        $context->matrix = count($lists) >= $count ? getCombinationToString($lists, $count) : [];

        // 过滤不带胆的数据
        foreach ($context->matrix as $k => $v) {
            $temp = explode(',', $v);
            $on = 0;
            foreach ($context->playNumberList[0] as $v2) {
                if (in_array($v2, $temp)) {
                    $on++;
                }
            }

            if ($on != count($context->playNumberList[0])) {
                unset($context->matrix[$k]);
            }
        }

        $context->betCount = count($context->matrix);
        if ($context->betCount == 0) {
            $context->stopNext()->setReason('注单计算异常');
        }
    }
}