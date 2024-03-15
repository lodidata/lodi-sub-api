<?php
namespace LotteryPlay\pk10;
use LotteryPlay\BaseResult;

class PK10Result extends BaseResult {
    
    // 定位胆 (同时时彩)
    public static function resultDDwd($context) {
        self::_init($context);
        foreach ($context->playNumberList as $k => $playNumbers) {
            foreach ($playNumbers as $playNumber) {
                if ($context->periodCodeList[$k] == $playNumber) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 大小单双 
    public static function resultQDxds($context) {
        self::_init($context);
        $compDefine = [
            '大' => function ($v) {return in_array($v, [6, 7, 8, 9, 10]) ? true : false;}, 
            '小' => function ($v) {return in_array($v, [1, 2, 3, 4, 5]) ? true : false;}, 
            '单' => function ($v) {return in_array($v, [1, 3, 5, 7, 9]) ? true : false;},
            '双' => function ($v) {return in_array($v, [2, 4, 6, 8, 10]) ? true : false;},
        ];

        $getText = function ($v) use ($compDefine) {
            $texts = [];
            foreach ($compDefine as $key => $func) {
                if ($func($v)) {
                    $texts[] = $key;
                }
            }
            return $texts;
        };

        foreach ($context->playNumberList as $place => $playNumbers) {
            $num = $context->periodCodeList[$place];
            $texts = $getText($num);
            foreach ($playNumbers ?? [] as $v) {
                $compFunc = $compDefine[$v];
                if ($compFunc($num) && in_array($v, $texts)) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 冠亚和值
    public static function resultQGyhz($context) {
        self::_init($context);
        $num = $context->periodCodeList[0] + $context->periodCodeList[1];
        $compDefine = [
            '和大' => function ($v) {return in_array($v, [12, 13, 14, 15, 16, 17, 18, 19]) ? true : false;}, 
            '和小' => function ($v) {return in_array($v, [3, 4, 5, 6, 7, 8, 9, 10, 11]) ? true : false;}, 
            '和单' => function ($v) {return $v % 2 == 1 ? true : false;},
            '和双' => function ($v) {return $v % 2 == 0 ? true : false;},
        ];
        foreach ($context->playNumberList[0] as $v) {
            $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
            if (isset($compDefine[$v]) && $compDefine[$v]($num)) {
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            } else if ($num == $v){
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 冠亚季和值
    public static function resultQGyjhz($context) {
        self::_init($context);
        $num = $context->periodCodeList[0] + $context->periodCodeList[1] + $context->periodCodeList[2];
        foreach ($context->playNumberList[0] as $v) {
            $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
            if ($num == $v){
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 龙虎斗
    public static function resultQLhd($context) {
        self::_init($context);
        $valDefine = ['冠' => 0, '亚'=> 1, '季' => 2, '四' => 3, '五' => 4, '六' => 5, '七' => 6, '八' => 7, '九' => 8, '十' => 9];
        $compDefine = [
            '龙' => function ($v1, $v2) {return $v1 > $v2 ? true : false;}, 
            '虎' => function ($v1, $v2) {return $v1 < $v2 ? true : false;}, 
        ];
        $num = $context->periodCodeList[0] + $context->periodCodeList[1] + $context->periodCodeList[2];
        foreach ($context->playNumberList[0] as $v) {
            // 冠十龙 => [0=>冠 1=>十 2=>龙];
            preg_match_all("/./u", $v, $vs);
            $vs = $vs[0];
            $v1 = $context->periodCodeList[$valDefine[$vs[0]]];
            $v2 = $context->periodCodeList[$valDefine[$vs[1]]];
            $compFunc = $compDefine[$vs[2]];
            if ($compFunc($v1, $v2)) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 猜第一
    public static function resultCCdy($context) {
        self::_init($context);
        foreach ($context->playNumberList[0] as $v) {
            if ($v == $context->periodCodeList[0]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }


    // 猜前二、三 ----- 十
    public static function resultCCde($context, $length = 2) {
        self::_init($context);
        foreach ($context->matrix as $v) {
            $succuss = true;
            for ($i = 0; $i < $length; $i++) {
                if ($v[$i] != $context->periodCodeList[$i]) {
                    $succuss = false;
                    break;
                }
            }

            if ($succuss) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }
}