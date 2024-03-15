<?php
namespace LotteryPlay\xy28;
use LotteryPlay\BaseResult;

class XY28Result extends BaseResult {
    // 大小单双
    public static function resultDDxds($context) {
        self::_init($context);
        $compDefine = [
            '大' => function ($periodCodeList) {return array_sum($periodCodeList) >= 14 ? true : false;}, 
            '小' => function ($periodCodeList) {return array_sum($periodCodeList) <= 13 ? true : false;}, 
            '单' => function ($periodCodeList) {$end = str_split(array_sum($periodCodeList));return end($end) % 2 == 1 ? true : false;},
            '双' => function ($periodCodeList) {$end = str_split(array_sum($periodCodeList));return end($end) % 2 == 0 ? true : false;},
            '小单' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [1, 3, 5, 7, 9, 11, 13]) ? true : false;}, 
            '小双' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [0, 2, 4, 6, 8, 10, 12]) ? true : false;}, 
            '大单' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [15, 17, 19, 21, 23, 25, 27]) ? true : false;},
            '大双' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [14, 16, 18, 20, 22, 24, 26]) ? true : false;},
            '极小' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [0, 1, 2, 3, 4, 5]) ? true : false;},
            '极大' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [22, 23, 24, 25, 26, 27]) ? true : false;},
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

        $texts = $getText($context->periodCodeList);

        foreach ($context->playNumberList[0] as $v) {
            if (in_array($v, $texts)) {
                $index =  array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 色波
    public static function resultSSb($context) {
        self::_init($context);
        $compDefine = [
            '红波' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [3, 6, 9, 12, 15, 18, 21, 24]) ? true : false;}, 
            '绿波' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [1, 4, 7, 10, 16, 19, 22, 25]) ? true : false;}, 
            '蓝波' => function ($periodCodeList) {return in_array(array_sum($periodCodeList), [2, 5, 8, 11, 17, 20, 23, 26]) ? true : false;},
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

        $texts = $getText($context->periodCodeList);

        foreach ($context->playNumberList[0] as $v) {
            if (in_array($v, $texts)) {
                $index =  array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 豹子 
    public static function resultSBz($context) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        $compDefine = [
            '豹子通选' => function ($v) {return $v[0] == $v[1] && $v[1] == $v[2] ? true : false;}, 
            // '顺子通选' => function ($v) {return $v[0] + 1 == $v[1] && $v[1] + 1 == $v[2] ? true : false;}, 
        ];

        foreach ($context->playNumberList[0] as $v) {
            foreach ($context->lotteryConfig['standardOddsDesc'] as $key => $val) {
                if (isset($compDefine[$v]) 
                    && $val == $v 
                    && $compDefine[$v]($periodCodeList)) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                } else if (!isset($compDefine[$v]) && $val == $v
                           && $v == $periodCodeList[0].$periodCodeList[1].$periodCodeList[2]
                ) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                }
            }
        }
    }

    // 顺子 2018-05-05 分离豹子和顺子为单独方法
    public static function resultSBz2($context) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        $compDefine = [
            // '豹子通选' => function ($v) {return $v[0] == $v[1] && $v[1] == $v[2] ? true : false;}, 
            '顺子通选' => function ($v) {
                sort($v);
                if($v[0] == 0 && ($v[1] == 1 || $v[1] == 8) && $v[2] == 9){
                    return true;
                }
                return $v[0] + 1 == $v[1] && $v[1] + 1 == $v[2]   ? true : false;
            },
        ];
        if (count(array_count_values($periodCodeList)) != 3) {
            return false;
        }

        foreach ($context->playNumberList[0] as $v) {
            foreach ($context->lotteryConfig['standardOddsDesc'] as $key => $val) {
                $vals = str_split($val);
                if (isset($compDefine[$v]) 
                    && $val == $v 
                    && $compDefine[$v]($periodCodeList)) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                } else if (!isset($compDefine[$v]) 
                           && $val == $v 
                           && in_array($periodCodeList[0], $vals) 
                           && in_array($periodCodeList[1], $vals) 
                           && in_array($periodCodeList[2], $vals)
                ) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                }
            }
        }
    }

    // 对子 (同时时彩)
    public static function resultSDz($context) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        $listCount = array_count_values($periodCodeList);
        $compDefine = [
            '对子通选' => function ($v) {return array_sum($v) == 3 ? true : false;}, 
        ];
        if (count($listCount) == 2) {
            foreach ($context->playNumberList[0] as $v) {
                foreach ($context->lotteryConfig['standardOddsDesc'] as $key => $val) {
                    $vs = str_split(strval($v));
                    if (isset($compDefine[$v]) 
                        && $val == $v 
                        && $compDefine[$v]($listCount)) {
                        $context->isWin[$key] = true;
                        $context->winBetCount[$key]++;
                    } else if (!isset($compDefine[$v]) && $val == $v 
                        && (($v == $periodCodeList[0].$periodCodeList[1] && $v != $periodCodeList[1].$periodCodeList[2])
                           || ($v == $periodCodeList[1].$periodCodeList[2] && $v != $periodCodeList[0].$periodCodeList[1])
                            || ($v == $periodCodeList[0].$periodCodeList[2] && $v != $periodCodeList[0].$periodCodeList[1])
                        )
                    ) {
                        $context->isWin[$key] = true;
                        $context->winBetCount[$key]++;
                    }
                }
            }
        }
    }

    // 和值 (同时时彩)
    public static function resultCHz($context) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        foreach ($context->playNumberList[0] as $v) {
            for ($i = 0; $i <= 27; $i++) {
                if ($i == $v 
                    && $periodCodeList[0] + $periodCodeList[1] + $periodCodeList[2] == $i) {
                    $context->isWin[$i] = true;
                    $context->winBetCount[$i]++;
                }
            }
        }
    }

    // 和值包三
    public static function resultCHzbs($context) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if ($periodCodeList[0] + $periodCodeList[1] + $periodCodeList[2] == $v[0]
                || $periodCodeList[0] + $periodCodeList[1] + $periodCodeList[2] == $v[1]
                || $periodCodeList[0] + $periodCodeList[1] + $periodCodeList[2] == $v[2]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 龙虎斗
    public static function resultCLHd($context) {
        self::_init($context);
        $valDefine = ['百' => 0, '十' => 1, '个' => 2];
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }
}
