<?php
namespace LotteryPlay\ssc;
// use LotteryPlay\Function\arrayValueEquals;
// use function LotteryPlay\Functions\matrix AS matrix;
use LotteryPlay\BaseResult;

/**
 * 从左到右->后到前
 * Class SSCResult
 * @package LotteryPlay\ssc
 */
class SSCResult extends BaseResult {
    
    // 直选复式
    public static function result5Zxfs($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        self::_init($context);
        foreach ($context->matrix as $v) {
            // 全中
            if ($v[0] == $context->periodCodeList[0]
            && $v[1] == $context->periodCodeList[1]
            && $v[2] == $context->periodCodeList[2]
            && $v[3] == $context->periodCodeList[3]
            && $v[4] == $context->periodCodeList[4]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 直选组合
    public static function result5Zxzh($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        foreach ($context->matrix as $v) {
            // 全中
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }

            // 后四
            if ($v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
            }

            // 后三
            if ($v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
            }

            // 后二
            if ($v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[3] = true;
                $context->winBetCount[3]++;
            }

            // 后一
            if ($v[4] == $context->periodCodeList[4]) {
                $context->isWin[4] = true;
                $context->winBetCount[4]++;
            }
        }
    }

    // 通选复式
    public static function result5Txfs($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        $context->debug = $context->matrix;
        foreach ($context->matrix as $v) {

            // 全中
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
                continue;
            }

            // 前三
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
                continue;
            }

            // 后三
            if ($v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
                continue;
            }

            // 前二
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                ) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
                continue;
            }

            // 后二
            if ($v[3] == $context->periodCodeList[3]
                && $v[4] == $context->periodCodeList[4]) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
                continue;
            }
        }
    }

    // 组选120
    public static function result5Comb120($context, $start = 0, $len = 5) {
        // 生成数组
        // $context->matrix = matrix($context->playNumberList);
        // // 初始化
        // self::_init($context);

        // foreach ($context->matrix as $v) {
        //     // 不按顺序全中
        //     if (arrayValueEquals2($v, $context->periodCodeList)) {
        //         $context->isWin = [0 => true];
        //         $context->winBetCount[0]++;
        //     }
        // }
        
        self::_init($context);

        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = explode(',', $v);
            $context->debug = [$v, $listCount];
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && isset($listCount[$v[2]])
                && isset($listCount[$v[3]])
                && isset($listCount[$v[4]])
                && $listCount[$v[0]] == 1
                && $listCount[$v[1]] == 1
                && $listCount[$v[2]] == 1
                && $listCount[$v[3]] == 1
                && $listCount[$v[4]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 组选60
    public static function result5Comb60($context) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (
                isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]]) 
                && isset($listCount[$playNumbers[2]]) 
                && isset($listCount[$playNumbers[3]]) 
                && $listCount[$playNumbers[0]] == 2
                && $listCount[$playNumbers[1]] == 1 
                && $listCount[$playNumbers[2]] == 1
                && $listCount[$playNumbers[3]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 组选
    public static function result5Comb30($context) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]]) 
                && isset($listCount[$playNumbers[2]]) 
                && $listCount[$playNumbers[0]] == 2 
                && $listCount[$playNumbers[1]] == 2 
                && $listCount[$playNumbers[2]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 组选20
    public static function result5Comb20($context) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]]) 
                && isset($listCount[$playNumbers[2]]) 
                && $listCount[$playNumbers[0]] == 3 
                && $listCount[$playNumbers[1]] == 1 
                && $listCount[$playNumbers[2]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 组选10
    public static function result5Comb10($context) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]])
                && $listCount[$playNumbers[0]] == 3 
                && $listCount[$playNumbers[1]] == 2
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 组选5
    public static function result5Comb5($context) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]])
                && $listCount[$playNumbers[0]] == 4 
                && $listCount[$playNumbers[1]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 百家乐
    public static function result5Baccarat($context) {
        // 万0 千1 百2 十3 个4
        self::_init($context);
        foreach ($context->playNumberList[0] as $playNumber) {
      
            if ($playNumber == '庄' 
                && $context->periodCodeList[0] + $context->periodCodeList[1] > 
                   $context->periodCodeList[3] + $context->periodCodeList[4]
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '庄对子' 
                && $context->periodCodeList[0] == $context->periodCodeList[1]  
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '庄豹子' 
                && $context->periodCodeList[0] == $context->periodCodeList[1]
                && $context->periodCodeList[1] == $context->periodCodeList[2]
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '庄天王' 
                && ($context->periodCodeList[0] + $context->periodCodeList[1] == 8 || $context->periodCodeList[0] + $context->periodCodeList[1] == 9)
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '闲' 
                && $context->periodCodeList[0] + $context->periodCodeList[1] < 
                   $context->periodCodeList[3] + $context->periodCodeList[4] 
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '闲对子' 
                && $context->periodCodeList[3] == $context->periodCodeList[4]
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            if ($playNumber == '闲豹子' 
                && $context->periodCodeList[3] == $context->periodCodeList[4]
                && $context->periodCodeList[4] ==  $context->periodCodeList[2]
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }

            // ? = 8 是天王？
            if ($playNumber == '闲天王' 
                && ($context->periodCodeList[3] + $context->periodCodeList[4] == 8 || $context->periodCodeList[3] + $context->periodCodeList[4] == 9)
            ) {
                $index = array_search($playNumber, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }

    // 一帆风顺 好事成双 三星报喜 四季发财
    public static function result5Yffs($context, $count = 1) {
        self::_init($context);

        $listCount = array_count_values($context->periodCodeList);
        foreach ($context->playNumberList[0] as $playNumber) {
            if (isset($listCount[$playNumber])
                && $listCount[$playNumber] >= $count
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后四复式
    public static function result4Hsfs($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        foreach ($context->matrix as $v) {
            if ($v[0] == $context->periodCodeList[1]
                && $v[1] == $context->periodCodeList[2]
                && $v[2] == $context->periodCodeList[3]
                && $v[3] == $context->periodCodeList[4]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后四组合
    public static function result4Hszh($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        foreach ($context->matrix as $v) {
            if ($v[0] == $context->periodCodeList[1]
                && $v[1] == $context->periodCodeList[2]
                && $v[2] == $context->periodCodeList[3]
                && $v[3] == $context->periodCodeList[4]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }

            if ($v[1] == $context->periodCodeList[2]
                && $v[2] == $context->periodCodeList[3]
                && $v[3] == $context->periodCodeList[4]
            ) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
            }

            if ($v[2] == $context->periodCodeList[3]
                && $v[3] == $context->periodCodeList[4]
            ) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
            }

            if ($v[3] == $context->periodCodeList[4]
            ) {
                $context->isWin[3] = true;
                $context->winBetCount[3]++;
            }
        }
    }

    // 前四复式
    public static function result4Qsfs($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        foreach ($context->matrix as $v) {
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    public static function result4Qszh($context) {
        // 生成数组
        $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        foreach ($context->matrix as $v) {
            if ($v[0] == $context->periodCodeList[0]
                && $v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }

            if ($v[1] == $context->periodCodeList[1]
                && $v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
            ) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
            }

            if ($v[2] == $context->periodCodeList[2]
                && $v[3] == $context->periodCodeList[3]
            ) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
            }

            if ($v[3] == $context->periodCodeList[3]
            ) {
                $context->isWin[3] = true;
                $context->winBetCount[3]++;
            }
        }
    }

    // 后四组选24 前四组选24
    public static function result4Hszx24($context, $start = 1, $len = 5) {
        
        // $context->matrix = matrix($context->playNumberList);
        // 初始化
        self::_init($context);

        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = explode(',', $v);
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && isset($listCount[$v[2]])
                && isset($listCount[$v[3]])
                && $listCount[$v[0]] == 1
                && $listCount[$v[1]] == 1
                && $listCount[$v[2]] == 1
                && $listCount[$v[3]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后四组选12 前四组选12
    public static function result4Hszx12($context, $start = 1, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]]) 
                && isset($listCount[$playNumbers[2]]) 
                && $listCount[$playNumbers[0]] == 2
                && $listCount[$playNumbers[1]] == 1 
                && $listCount[$playNumbers[2]] == 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后四组选6 前四组选6
    public static function result4Hszx6($context, $start = 1, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $playNumbers) {
            $playNumbers = explode(',', $playNumbers);
            if (isset($listCount[$playNumbers[0]])
                && isset($listCount[$playNumbers[1]])
                && $listCount[$playNumbers[0]] == 2
                && $listCount[$playNumbers[1]] == 2
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后四组选4 前四组选4
    public static function result4Hszx4($context, $start = 1, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (isset($listCount[$playNumbers[0]])
                && isset($listCount[$playNumbers[1]])
                && $listCount[$playNumbers[0]] == 3
                && $listCount[$playNumbers[1]] == 1 
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后三直选复式
    public static function result3Zxfs($context, $start = 2, $len = 4) {
        self::_init($context);
        $context->matrix = matrix($context->playNumberList);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->matrix as $v) {
            if ($periodCodeList[0] == $v[0]
                && $periodCodeList[1] == $v[1]
                && $periodCodeList[2] == $v[2]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 直选组合
    public static function result3Zxzh($context, $start = 2, $len = 4) {
        self::_init($context);
        $context->matrix = matrix($context->playNumberList);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->matrix as $v) {
            
            if ($periodCodeList[0] == $v[0]
                && $periodCodeList[1] == $v[1]
                && $periodCodeList[2] == $v[2]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }

            if ($periodCodeList[1] == $v[1]
                && $periodCodeList[2] == $v[2]) {
                $context->isWin[1] = true;
                $context->winBetCount[1]++;
            }

            if ($periodCodeList[2] == $v[2]) {
                $context->isWin[2] = true;
                $context->winBetCount[2]++;
            }
        }
    }

    // 前三组选顺序不一致
    public static function resulth3zxsxbgz($context, $start = 2, $len = 3) {
        self::_init($context);
        $context->matrix = matrix($context->playNumberList);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->matrix as $v) {
            if ($periodCodeList[0] == $v[0]
                && $periodCodeList[1] == $v[1]
                && $periodCodeList[2] == $v[2]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }elseif ($periodCodeList[0] == $v[0]
                && $periodCodeList[1] == $v[2]
                && $periodCodeList[2] == $v[1]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }elseif ($periodCodeList[0] == $v[1]
                && $periodCodeList[1] == $v[0]
                && $periodCodeList[2] == $v[2]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }elseif ($periodCodeList[0] == $v[1]
                && $periodCodeList[1] == $v[2]
                && $periodCodeList[2] == $v[0]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }elseif ($periodCodeList[0] == $v[2]
                && $periodCodeList[1] == $v[1]
                && $periodCodeList[2] == $v[0]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }elseif ($periodCodeList[0] == $v[2]
                && $periodCodeList[1] == $v[0]
                && $periodCodeList[2] == $v[1]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 直选和值
    public static function result3Zxhz($context, $start = 2, $len = 4) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
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

    // 直选跨度
    public static function result3Zxkd($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->playNumberList[0] as $v) {
            for ($i = 0; $i <= 9; $i++) {
                if ($i == $v 
                    && max($periodCodeList[0], $periodCodeList[1] , $periodCodeList[2])
                    - min($periodCodeList[0], $periodCodeList[1] , $periodCodeList[2])
                    == $i) {
                    $context->isWin[$i] = true;
                    $context->winBetCount[$i]++;
                }
            }
        }
    }

    // 组三包号
    public static function result3Zsbh($context, $start = 2, $len = 5) {
        self::_init($context);

        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        if (count($listCount) == 2) {
            foreach ($context->matrix as $v) {
                $v = explode(',', $v);
                if (isset($listCount[$v[0]])
                    && isset($listCount[$v[1]])
                    && (($listCount[$v[0]] > 1 && $listCount[$v[1]] == 1) || ($listCount[$v[0]] == 1 && $listCount[$v[1]] > 1))
                ) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 组三直选
    public static function result3Zszx($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList as $v) {
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

    // 组六包号
    public static function result3Zlbh($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = explode(',', $v);
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

    // 后三豹子 
    public static function result3Hsbz($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $compDefine = [
            '豹子通选' => function ($v) {return $v[0] == $v[1] && $v[1] == $v[2] ? true : false;}, 
        ];
        foreach ($context->playNumberList[0] as $v) {
            foreach ($context->lotteryConfig['standardOddsDesc'] as $key => $val) {
                $vals = str_split($val);
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

    // 后三顺子
    public static function result3Hsbz2($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $count = count(array_count_values($periodCodeList));
        if ($count != 3) {
            return;
        }

        $compDefine = [
            '顺子通选' => function ($v) {
                sort($v);
                if($v[0] == 0 && ($v[1] == 1 || $v[1] == 8) && $v[2] == 9){
                    return true;
                }
                 return $v[0] + 1 == $v[1] && $v[1] + 1 == $v[2] ? true : false;
                },
        ];


        foreach ($context->playNumberList[0] as $v) {
            foreach ($context->lotteryConfig['standardOddsDesc'] as $key => $val) {
                $vals = str_split($val);
                if (isset($compDefine[$v]) 
                    && $val == $v 
                    && $compDefine[$v]($periodCodeList)) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                } else if (!isset($compDefine[$v]) && $val == $v 
                           && in_array($periodCodeList[0],$vals)
                           && in_array($periodCodeList[1],$vals)
                           && in_array($periodCodeList[2],$vals)
                ) {
                    $context->isWin[$key] = true;
                    $context->winBetCount[$key]++;
                }
            }
        }
    }

    // 后三对子
    public static function result3Hsdz($context, $start = 2, $len = 5) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $compDefine = [
            '对子通选' => function ($v) {return array_sum($v) == 3 ? true : false;}, 
        ];
        $listCount = array_count_values($periodCodeList);
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
                        && ($v == $periodCodeList[0].$periodCodeList[1] 
                        || $v == $periodCodeList[1].$periodCodeList[2]
                        || $v == $periodCodeList[0].$periodCodeList[2])
                    ) {
                        $context->isWin[$key] = true;
                        $context->winBetCount[$key]++;
                    }
                }
            }
        }
    }

    // 前二直选复式
    public static function result2Zxfs($context, $start = 3, $len = 2) {
        self::_init($context);
        $context->matrix = matrix($context->playNumberList);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->matrix as $v) {
            if ($periodCodeList[0] == $v[0]
                && $periodCodeList[1] == $v[1]) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 前二直选和值
    public static function result2Zxhz($context, $start = 3, $len = 2) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->playNumberList[0] as $v) {
            for ($i = 0; $i <= 18; $i++) {
                if ($i == $v 
                    && $periodCodeList[0] + $periodCodeList[1] == $i) {
                    $context->isWin[$i] = true;
                    $context->winBetCount[$i]++;
                }
            }
        }
    }

    // 直选跨度
    public static function result2Zxkd($context, $start = 3, $len = 4) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        foreach ($context->playNumberList[0] as $v) {
            for ($i = 0; $i <= 9; $i++) {
                if ($i == $v 
                    && max($periodCodeList[0], $periodCodeList[1])
                    - min($periodCodeList[0], $periodCodeList[1])
                    == $i) {
                    $context->isWin[$i] = true;
                    $context->winBetCount[$i]++;
                }
            }
        }
    }

    // 组选复式
    public static function result2Zuxfs($context, $start = 3, $len = 4) {
        self::_init($context);
        $periodCodeList = array_splice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList as $playNumbers) {
            if (
                isset($listCount[$playNumbers[0]]) 
                && isset($listCount[$playNumbers[1]]) 
                && $listCount[$playNumbers[0]] == 1
                && $listCount[$playNumbers[1]] == 1) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 龙虎斗
    public static function resultLMLhd($context) {
        self::_init($context);
        $valDefine = ['万' => 0, '千' => 1, '百' => 2, '十' => 3, '个' => 4];
        $compDefine = [
            '龙' => function ($v1, $v2) {return $v1 > $v2 ? true : false;}, 
            '虎' => function ($v1, $v2) {return $v1 < $v2 ? true : false;}, 
            '合' => function ($v1, $v2) {return $v1 == $v2 ? true : false;},
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

    // 五字和
    public static function resultLMWzh($context) {
        self::_init($context);
        $compDefine = [
            '总数大' => function ($periodCodeList) {return array_sum($periodCodeList) >= 23 ? true : false;},
            '总数小' => function ($periodCodeList) {return array_sum($periodCodeList) <= 22 ? true : false;},
            '总数单' => function ($periodCodeList) {return array_sum($periodCodeList) % 2 == 1 ? true : false;},
            '总数双' => function ($periodCodeList) {return array_sum($periodCodeList) % 2 == 0 ? true : false;},
            '和尾数大' => function ($periodCodeList) {$vals = str_split(array_sum($periodCodeList));return end($vals) >= 5 ? true : false;},
            '和尾数小' => function ($periodCodeList) {$vals = str_split(array_sum($periodCodeList));return end($vals) <= 4 ? true : false;},
            '和尾数质' => function ($periodCodeList) {$vals = str_split(array_sum($periodCodeList));return in_array(end($vals), [1, 2, 3, 5, 7]) ? true : false;},
            '和尾数合' => function ($periodCodeList) {$vals = str_split(array_sum($periodCodeList));return in_array(end($vals), [0, 4, 6, 8, 9]) ? true : false;},
        ];

        foreach ($context->playNumberList[0] as $v) {
            $compFunc = $compDefine[$v];
            if ($compFunc($context->periodCodeList)) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 定位胆
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

    // 后三一码 前三一码 中三一码 后四一码
    public static function resultBHsym($context, $start = 2, $len = 4) {
        self::_init($context);
        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList[0] as $k => $playNumber) {
            if (isset($listCount[$playNumber])
                && $listCount[$playNumber] >= 1) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
            
        }
    }
    // 后二一码
    public static function resultBH2ym($context, $start = 0, $len = 2) {
        self::_init($context);
        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->playNumberList[0] as $k => $playNumber) {
            if (isset($listCount[$playNumber])
                && $listCount[$playNumber] >= 1) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }

        }
    }

    // 后三二码 前三二码 中三二码 后四二码 五星二码
    public static function resultBHsem($context, $start = 2, $len = 4) {
        self::_init($context);
        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = explode(',', $v);
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && $listCount[$v[0]] >= 1
                && $listCount[$v[1]] >= 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 五星三码
    public static function resultBWxsm($context, $start = 0, $len = 4) {
        self::_init($context);
        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        $listCount = array_count_values($periodCodeList);
        foreach ($context->matrix as $v) {
            $v = explode(',', $v);
            if (isset($listCount[$v[0]])
                && isset($listCount[$v[1]])
                && isset($listCount[$v[2]])
                && $listCount[$v[0]] >= 1
                && $listCount[$v[1]] >= 1
                && $listCount[$v[2]] >= 1
            ) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 单球
    public static function resultDDq($context, $start, $len) {
        self::_init($context);
        $compDefine = [
            '大' => function ($v) {return in_array($v, [5, 6, 7, 8, 9]) ? true : false;}, 
            '小' => function ($v) {return in_array($v, [0, 1, 2, 3, 4]) ? true : false;}, 
            '单' => function ($v) {return in_array($v, [1, 3, 5, 7, 9]) ? true : false;},
            '双' => function ($v) {return in_array($v, [0, 2, 4, 6, 8]) ? true : false;},
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
        $periodCodeList = array_slice($context->periodCodeList, $start, $len);
        foreach ($context->playNumberList as $place => $playNumbers) {
            $num = $periodCodeList[$place];
            $texts = $getText($num);
            foreach ($playNumbers ?? [] as $v) {
                if (empty($v)) {
                    continue;
                }
                $compFunc = $compDefine[$v];
                if ($compFunc($num) && in_array($v, $texts)) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }
        }
    }

    // 后二 前二
    public static function resultDHe($context, $start = 3, $end = 4) {
        self::_init($context);
        $compDefine = [
            '大' => function ($v) {return in_array($v, [5, 6, 7, 8, 9]) ? true : false;}, 
            '小' => function ($v) {return in_array($v, [0, 1, 2, 3, 4]) ? true : false;}, 
            '单' => function ($v) {return in_array($v, [1, 3, 5, 7, 9]) ? true : false;},
            '双' => function ($v) {return in_array($v, [0, 2, 4, 6, 8]) ? true : false;},
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
        $periodCodeList = array_slice($context->periodCodeList, $start, $end);
        $context->matrix = matrix($context->playNumberList);
        $num1 = $periodCodeList[0];
        $texts1 = $getText($num1);

        $num2 = $periodCodeList[1];
        $texts2 = $getText($num2);

        foreach ($context->matrix as $v) {
            if (in_array($v[0], $texts1) && in_array($v[1], $texts2)) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }

    // 后三 前三
    public static function resultDHs($context, $start = 2, $end = 4) {
        self::_init($context);
        $compDefine = [
            '大' => function ($v) {return in_array($v, [5, 6, 7, 8, 9]) ? true : false;}, 
            '小' => function ($v) {return in_array($v, [0, 1, 2, 3, 4]) ? true : false;}, 
            '单' => function ($v) {return in_array($v, [1, 3, 5, 7, 9]) ? true : false;},
            '双' => function ($v) {return in_array($v, [0, 2, 4, 6, 8]) ? true : false;},
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
        
        $periodCodeList = array_slice($context->periodCodeList, $start, $end);
        $context->matrix = matrix($context->playNumberList);

        $num1 = $periodCodeList[0];
        $texts1 = $getText($num1);

        $num2 = $periodCodeList[1];
        $texts2 = $getText($num2);

        $num3 = $periodCodeList[2];
        $texts3 = $getText($num3);
        foreach ($context->matrix as $v) {
            if (in_array($v[0], $texts1) && in_array($v[1], $texts2) && in_array($v[2], $texts3)) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }
}