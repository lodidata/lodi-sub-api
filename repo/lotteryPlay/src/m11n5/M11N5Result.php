<?php
namespace LotteryPlay\m11n5;
use LotteryPlay\BaseResult;

class M11N5Result extends BaseResult {
    
    // 前三码 直选复式
    public static function resultS3Zxfs($context) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, 0, 3);
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if ($periodCodeList[0] == $v[0] 
                && $periodCodeList[1] == $v[1] 
                && $periodCodeList[2] == $v[2]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 前三码 组选复式 组选胆拖
    public static function resultS3Zxfs2($context) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, 0, 3);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && isset($listCount[$v[2]])
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 前二码 直选复式
    public static function resultS2Zxfs($context) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, 0, 2);
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if ($periodCodeList[0] == $v[0] 
                && $periodCodeList[1] == $v[1] 
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 前二码 组选复式 组选胆拖
    public static function resultS2Zxfs2($context) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, 0, 2);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // M中N复式 M中N胆拖
    public static function resultSMzns($context, $length = 1) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        $context->matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];
        foreach ($context->matrix as $playNumbers) {
            $succCount = 0;
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            foreach ($playNumbers as $playNumber) {
                if (isset($listCount[$playNumber])) {
                    $succCount++;
                }
            }

            if ($succCount >= $length) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 定位胆 (同时时彩)
    public static function resultFDwd($context) {
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
    public static function resultFDsds($context) {
        self::_init($context);
        self::_init($context);
        $compDefine = [
            '大' => function ($v) {return in_array($v, [6, 7, 8, 9, 10, 11]) ? true : false;}, 
            '小' => function ($v) {return in_array($v, [1, 2, 3, 4, 5]) ? true : false;}, 
            '单' => function ($v) {return in_array($v, [1, 3, 5, 7, 9, 11]) ? true : false;},
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
                    $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                    $context->isWin[$index] = true;
                    $context->winBetCount[$index]++;
                }
            }
        }
    }

    // 不定位码
    public static function resultFBdwn($context) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, 0, 3);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList[0] as $v) {
            if (isset($listCount[$v])
                || isset($listCount[$v])
                || isset($listCount[$v])
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 龙虎斗
    public static function resultFlhd($context) {
        self::_init($context);
        $valDefine = ['一' => 0, '二' => 1, '三' => 2, '四' => 3, '五' => 4];
        $compDefine = [
            '龙' => function ($v1, $v2) {return $v1 > $v2 ? true : false;}, 
            '虎' => function ($v1, $v2) {return $v1 < $v2 ? true : false;}, 
        ];

        foreach ($context->playNumberList[0] as $v) {
            // 分割字符，万千虎 [0=>万 1=>千 2=>虎]
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

    // 定向单双
    public static function resultFDxds($context) {
        self::_init($context);
        $compDefine = [
            '单' => function ($v) {return $v % 2 == 1 ? true : false;}, 
            '双' => function ($v) {return $v % 2 == 0 ? true : false;}, 
        ];

        $listCount = ['单' => 0, '双' => 0];
        foreach ($context->periodCodeList as $v) {
            if ($compDefine['单']($v)) {
                $listCount['单']++;
            } else {
                $listCount['双']++;
            }
        }

        foreach ($context->playNumberList[0] as $v) {
            // 分割字符，万千虎 [0=>万 1=>千 2=>虎]
            preg_match_all("/./u", $v, $vs);
            $vs = $vs[0];

            if ($vs[0] == $listCount['单'] && $vs[2] == $listCount['双']) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 定向中位
    public static function resultFDxzw($context) {
        self::_init($context);
        foreach ($context->playNumberList[0] as $v) {
            if ($context->periodCodeList[2] == $v
            ) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }
}