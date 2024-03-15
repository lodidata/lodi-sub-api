<?php
namespace LotteryPlay\q3;
use LotteryPlay\BaseResult;
use Logic\Set\SystemConfig;
class Q3Result extends BaseResult {
    
    // 二不同标准
    public static function resultEEbtbz($context) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        if (count($listCount) >= 2) {
            foreach ($context->matrix as $v) {
                $v = explode(',', $v);
                if (isset($listCount[$v[0]])
                    && isset($listCount[$v[1]])
                    && ($listCount[$v[0]] >= 1 && $listCount[$v[1]] >= 1)
                ) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 二不同号胆拖
    public static function resultEEbtbt($context) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        if (count($listCount) >= 2) {
            foreach ($context->matrix as $v) {
                $v = explode(',', $v);
                if (isset($listCount[$v[0]])
                    && isset($listCount[$v[1]])
                    && ($listCount[$v[0]] >= 1 || $listCount[$v[1]] >= 1)
                ) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 二同号标准
    public static function resultEEthbz2($context) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->matrix as $v) {
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && $listCount[$v[0]] == 2
                && $listCount[$v[1]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }


    // 二同号复式
    public static function resultEEthbz22($context) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList[0] as $v) {
            if (isset($listCount[$v])
                && $listCount[$v] == 2
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 三不同标准 三不同胆拖
    public static function resultSEbtbz($context) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->matrix as $v) {
            $v = !is_array($v) ? explode(',', $v) : $v;
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && isset($listCount[$v[2]])
                && $listCount[$v[0]] == 1
                && $listCount[$v[1]] == 1
                && $listCount[$v[2]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 三同号
    public static function resultSSth($context) {
        self::_init($context);
        $merge = function ($periodCodeList) {return $periodCodeList[0].$periodCodeList[1].$periodCodeList[2];};

        $compDefine = [
            '111' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '111' ? true : false;}, 
            '222' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '222' ? true : false;}, 
            '333' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '333' ? true : false;}, 
            '444' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '444' ? true : false;}, 
            '555' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '555' ? true : false;}, 
            '666' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '666' ? true : false;}, 
            '三同号通选' => function ($periodCodeList) {return $periodCodeList[0] == $periodCodeList[1] && $periodCodeList[1] ==  $periodCodeList[2] ? true : false;}
        ];

        foreach ($context->playNumberList[0] as $v) {
            $compFunc = $compDefine[$v];
            if ($compFunc($context->periodCodeList)) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 三连号
    public static function resultSSlh($context) {
        self::_init($context);
        $merge = function ($periodCodeList) {return $periodCodeList[0].$periodCodeList[1].$periodCodeList[2];};

        $compDefine = [
            '123' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '123' ? true : false;}, 
            '234' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '234' ? true : false;}, 
            '345' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '345' ? true : false;}, 
            '456' => function ($periodCodeList) use ($merge) {return $merge($periodCodeList) == '456' ? true : false;}, 
            '三连号通选' => function ($periodCodeList) {return $periodCodeList[0] == $periodCodeList[1] - 1 && $periodCodeList[1] ==  $periodCodeList[2] - 1 ? true : false;}
        ];

        foreach ($context->playNumberList[0] as $v) {
            $compFunc = $compDefine[$v];
            if ($compFunc($context->periodCodeList)) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 点数
    public static function resultSds($context) {
        self::_init($context);

        // 判断是否豹子
        $bz = function ($periodCodeList) {
            $q3bz_tongsha = SystemConfig::getModuleSystemConfig('lottery')['q3bz_tongsha'] ?? 1;
            if($periodCodeList[0] == $periodCodeList[1] && $periodCodeList[1] == $periodCodeList[2]) {
                $baozi = true;
            }else{
                $baozi = false;
            }
            if($q3bz_tongsha && $baozi) {     //豹子通杀开关通杀开启   并且是豹子 则通杀
                return false;
            }
            return true;
        };

        $compDefine = [
            '大' => function ($periodCodeList, $sum) use ($bz) {return $bz($periodCodeList) && $sum > 10 ? true : false;}, 
            '小' => function ($periodCodeList, $sum) use ($bz) {return $bz($periodCodeList) && $sum <= 10 ? true : false;}, 
            '单' => function ($periodCodeList, $sum) use ($bz) {return $bz($periodCodeList) && $sum % 2 == 1 ? true : false;}, 
            '双' => function ($periodCodeList, $sum) use ($bz) {return $bz($periodCodeList) && $sum % 2 == 0 ? true : false;}, 
        ];
        $sum = array_sum($context->periodCodeList);
        foreach ($context->playNumberList[0] as $v) {
            if (isset($compDefine[$v]) && $compDefine[$v]($context->periodCodeList, $sum)) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            } else if (!isset($compDefine[$v]) && $v == $sum) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 骰宝
    public static function resultStb($context) {
        self::_init($context);

        $compDefine = [
            '骰宝' => function ($periodCodeList, $nums) {$nums = current($nums);return  $periodCodeList[0] == $nums || $periodCodeList[1] == $nums || $periodCodeList[2] == $nums ? true : false;}, 
            '短牌' => function ($periodCodeList, $nums) {$nums = current($nums);return ($periodCodeList[0] == $nums && $periodCodeList[1] == $nums) || ($periodCodeList[1] == $nums && $periodCodeList[2] == $nums) || ($periodCodeList[0] == $nums && $periodCodeList[2] == $nums)? true : false;}, 
            '长牌' => function ($periodCodeList, $v) {
                $listCount = array_count_values($periodCodeList);
                return isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && $listCount[$v[0]] == 1
                && $listCount[$v[1]] == 1
                ? true : false;
            }, 
        ];
        foreach ($context->playNumberList[0] as $v) {
            $funcName = str_replace([1,2,3,4,5,6], '', $v);
            $nums = str_split(str_replace($funcName, '', $v));
            $func = $compDefine[$funcName];
            if ($func($context->periodCodeList, $nums)) {
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 骰宝2
    public static function resultStb2($context) {
        self::_init($context);

        $compDefine = [
            '骰宝' => function ($periodCodeList, $nums) {$nums = current($nums);return  $periodCodeList[0] == $nums || $periodCodeList[1] == $nums || $periodCodeList[2] == $nums ? true : false;}, 
            '短牌' => function ($periodCodeList, $nums) {$nums = current($nums);return ($periodCodeList[0] == $nums && $periodCodeList[1] == $nums) || ($periodCodeList[1] == $nums && $periodCodeList[2] == $nums) || ($periodCodeList[0] == $nums && $periodCodeList[2] == $nums)? true : false;}, 
            '长牌' => function ($periodCodeList, $v) {
                $listCount = array_count_values($periodCodeList);
                return isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && $listCount[$v[0]] == 1
                && $listCount[$v[1]] == 1
                ? true : false;
            }, 
        ];
        foreach ($context->playNumberList[0] as $v) {
            $funcName = str_replace([1,2,3,4,5,6], '', $v);
            $nums = str_split(str_replace($funcName, '', $v));
            $func = $compDefine[$funcName];
            if ($func($context->periodCodeList, $nums)) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }
}