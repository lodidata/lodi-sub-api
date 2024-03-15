<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/4
 * Time: 14:53
 */

namespace LotteryPlay\lhc;

use LotteryPlay\BaseResult;
use Overtrue\ChineseCalendar\Calendar;

class LHCResult extends BaseResult {

    static $openTime = false;
    /**
     * 生肖对应英文
     */
    const LHC_ENGLISH = [
        '鼠' => 'mouse',
        '牛' => 'cow',
        '虎' => 'tiger',
        '兔' => 'rabbit',
        '龙' => 'dragon',
        '蛇' => 'snake',
        '马' => 'horse',
        '羊' => 'sheep',
        '猴' => 'monkey',
        '鸡' => 'chicken',
        '狗' => 'dog',
        '猪' => 'pig'
    ];
    /**
     * 色波 对应 英文
     */
    const LHC_COLOR_ENGLISH = [
        '红波' => 'red',
        '蓝波' => 'blue',
        '绿波' => 'green',
    ];

    /**
     * 生肖 对应 色波
     */
    const LHF_SB = [
        '红波' => ['01', '02', '07', '08', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'],
        '蓝波' => ['03', '04', '09', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'],
        '绿波' => ['05', '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'],
    ];


    /**
     * @param $context
     * @param $site 位置
     * 根据位置获取球号中奖
     */
    public static function resultBaseQiuHao($context, $site, $isRandom = false) {
        self::_init($context);
        $periodCodeList = $context->periodCodeList;
        foreach ($context->playNumberList[0] as $key => $v) {
            if ($isRandom) {
                for ($i = 0; $i <= 5; $i++) {
                    if ($v == $periodCodeList[$i]) {
                        $context->isWin[0] = true;
                        $context->winBetCount[0]++;
                        //print_r($key);
                    }
                }
            } else {
                if ($v == $periodCodeList[$site - 1]) {
                    $context->isWin[0] = true;
                    $context->winBetCount[0]++;
                }
            }

        }
    }

    /**
     * @param $context
     * 特码号 选择一个号码与开奖第7位号码相同即中奖
     */
    public static function resultTtmh($context) {
        self::resultBaseQiuHao($context, 7);
    }

    /**
     * @param $context
     * 正码号 选择一个号码.开奖第1-6位任意一个位置出现选择号码相同即中奖
     */
    public static function resultZmh($context) {
        self::resultBaseQiuHao($context, 0, true);
    }

    /**
     * @param $context
     * 正码一 选择一个号码为开奖第1位号码相同即中奖
     */
    public static function resultZmy($context) {
        self::resultBaseQiuHao($context, 1);
    }

    /**
     * @param $context
     * 正码二 选择一个号码为开奖第2位号码相同即中奖
     */
    public static function resultZme($context) {
        self::resultBaseQiuHao($context, 2);
    }

    /**
     * @param $context
     * 正码三 选择一个号码为开奖第3位号码相同即中奖
     */
    public static function resultZms($context) {
        self::resultBaseQiuHao($context, 3);
    }

    /**
     * @param $context
     * 正码四 选择一个号码为开奖第4位号码相同即中奖
     */
    public static function resultZmsi($context) {
        self::resultBaseQiuHao($context, 4);
    }

    /**
     * @param $context
     * 正码五 选择一个号码为开奖第5位号码相同即中奖
     */
    public static function resultZmw($context) {
        self::resultBaseQiuHao($context, 5);
    }

    /**
     * @param $context
     * 正码六 选择一个号码为开奖第6位号码相同即中奖
     */
    public static function resultZml($context) {
        self::resultBaseQiuHao($context, 6);
    }

    /**
     * @param $context
     * 任选中一
     */
    public static function resultBaseRenxuan($context) {
        self::_init($context);
        /*$periodCodeList = $context->periodCodeList;
        foreach ($context->playNumberList[0] as $key => $v) {
            $key = 0;
            for ($i = 0; $i <= 6; $i++) {
                if ($v == $periodCodeList[$i]) {
                    $key++;
                }
            }
            if ($key == 1) {
                $context->isWin[$key] = true;
                $context->winBetCount = 1;
            }
        }*/

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
            if ($succCount == 1) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }

    }

    /**
     * @param $context
     * 任选不中
     */
    public static function resultBaseRenxuanNo($context, $length = 5) {
        self::_init($context);
        $listCount = array_count_values($context->periodCodeList);
        $context->matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];
        foreach ($context->matrix as $playNumbers) {
            $succCount = 0;
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            foreach ($playNumbers as $playNumber) {
                if (!isset($listCount[$playNumber])) {
                    $succCount++;
                }
            }
            if ($succCount == $length) {
                $context->isWin[0] = true;
                $context->winBetCount[0]++;
            }
        }
    }


    // 特码双面 '大', '小', '单', '双', '小单', '小双', '大单', '大双','和大','和小','和单','和双','尾大','尾小'
    public static function resultTtmsm($context) {
        self::_init($context);

        $compDefine = [
            '大' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[0] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] >= 25 ? true : false;
                }
            },
            '小' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[1] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] <= 24 ? true : false;
                }
            },
            '单' => function ($periodCodeList) use ($context) {
                $end = $periodCodeList[6];
                if ($end == 49) {
                    $context->oddsOrigin[2] = 1;
                    return true;
                } else {
                    return $end % 2 == 1 ? true : false;
                }
            },
            '双' => function ($periodCodeList) use ($context) {
                $end = $periodCodeList[6];
                if ($end == 49) {
                    $context->oddsOrigin[3] = 1;
                    return true;
                } else {
                    return $end % 2 == 0 ? true : false;
                }
            },
            '大单' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[4] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] >= 25 && $periodCodeList[6] % 2 == 1 ? true : false;
                }
            },
            '小单' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[5] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] <= 24 && $periodCodeList[6] % 2 == 1 ? true : false;
                }
            },
            '大双' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[6] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] >= 25 && $periodCodeList[6] % 2 == 0 ? true : false;
                }
            },
            '小双' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[7] = 1;
                    return true;
                } else {
                    return $periodCodeList[6] <= 24 && $periodCodeList[6] % 2 == 0 ? true : false;
                }
            },
            '和大' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[8] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[6]);
                    if (strlen($period) == 1) {
                        return $period >= 7 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return $number1 + $number2 >= 7 ? true : false;
                    }
                }
            },
            '和小' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[9] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[6]);
                    if (strlen($period) == 1) {
                        return $period <= 6 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return $number1 + $number2 <= 6 ? true : false;
                    }
                }
            },
            '和单' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[10] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[6]);
                    if (strlen($period) == 1) {
                        return $period % 2 == 1 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return ($number1 + $number2) % 2 == 1 ? true : false;
                    }
                }
            },
            '和双' => function ($periodCodeList) use ($context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[11] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[6]);
                    if (strlen($period) == 1) {
                        return $period % 2 == 0 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return ($number1 + $number2) % 2 == 0 ? true : false;
                    }
                }
            },
            '尾大' => function ($periodCodeList) {
                $period = intval($periodCodeList[6]);
                if (strlen($period) == 1) {
                    return $period >= 5 ? true : false;
                } else {
                    $num1 = str_split($period);
                    $number1 = $num1[count($num1) - 1];
                    //$number2 = $num1[count($num1) - 2];
                    return $number1 >= 5 ? true : false;
                }
            },
            '尾小' => function ($periodCodeList) {
                $period = intval($periodCodeList[6]);
                if (strlen($period) == 1) {
                    return $period <= 4 ? true : false;
                } else {
                    $num1 = str_split($period);
                    $number1 = $num1[count($num1) - 1];
                    //$number2 = $num1[count($num1) - 2];
                    return $number1 <= 4 ? true : false;
                }
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }


    /**
     * 通过号码，获取对应生肖信息
     * @param string $number
     * @return array
     */
    public static function getNumberInfo(string $number) {
        $result = [];
        foreach (self::LHC_ENGLISH as $sx => $value) {
            $number_list = self::getShengxiao($sx);
            if (in_array($number,$number_list)){
                foreach (self::LHF_SB as $color => $sb){
                    if (in_array($number,$sb)){
                        $result['sx'] = $sx;
                        $result['en'] = self::LHC_ENGLISH[$sx];
                        $result['color'] = self::LHC_COLOR_ENGLISH[$color];
                        return $result;
                    }
                }

            }
        }
        return $result;
    }

    /**
     * sx 某个生肖
     *
     * @param $sx
     *
     * @return array
     *
     * 获取对应生肖 号码
     */
    public static function getShengxiao($sx) {
        if (self::$openTime === false) {
            $date = explode('-', date('Y-m-d'));
        } else {
            $date = explode('-', date('Y-m-d', strtotime(self::$openTime)));
        }

        //获取生肖需要使用农历年计算
        $calendar = new Calendar();
        $result = $calendar->solar($date[0], $date[1], $date[2]);

        $year = $result['lunar_year'];

        /**
         * 年份除以12，余数0-11分别对应
         * '猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊'
         */
        $sx_year = $year % 12;
        $shengxiao = ['猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊'];

        $data = [];
        foreach ($shengxiao as $key => $value) {
            $current_num = [01, 13, 25, 37, 49];

            $data[$value] = array_map(function ($v) use ($shengxiao, $value, $sx_year) {
                return str_pad($v + $sx_year - array_search($value, $shengxiao), 2, '0', STR_PAD_LEFT);
            }, $current_num);

            $data[$value] = array_filter($data[$value], function ($v) {
                return $v <= 49 && $v >= 1;
            });
        }

        return $data[$sx];
    }

    /*
     * 特码生肖
     */
    public static function ResultTtmsx($context) {
        self::_init($context);

        self::$openTime = $context->openTime;

        /* '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪',*/
        $compDefine = [
            '鼠' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('鼠')) ? true : false;
            },
            '牛' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('牛')) ? true : false;
            },
            '虎' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('虎')) ? true : false;
            },
            '兔' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('兔')) ? true : false;
            },
            '龙' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('龙')) ? true : false;
            },
            '蛇' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('蛇')) ? true : false;
            },
            '马' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('马')) ? true : false;
            },
            '羊' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('羊')) ? true : false;
            },
            '猴' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('猴')) ? true : false;
            },
            '鸡' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('鸡')) ? true : false;
            },
            '狗' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('狗')) ? true : false;
            },
            '猪' => function ($periodCodeList) {
                return in_array($periodCodeList[6], self::getShengxiao('猪')) ? true : false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }

    }



    /**
     * @param $context
     * @param $site
     * 生肖基类方法   开奖1-site 位置
     */
    /* public static function SxBaseRand($context,$site){
          self::_init($context);
          $periodCodeList = $context->periodCodeList;
          foreach ($context->playNumberList[0] as $key => $v) {
              $data = self::getShengxiao($v);
              for ($i = 0; $i <= $site-1; $i++) {
                  if (in_array($periodCodeList[$i],$data)) {
                      $context->isWin[$key] = true;
                      $context->winBetCount[$key]++;
                  }
              }
          }
      }*/
    public static function SxBaseRand($context, $site, $isDouble = false) {
        self::_init($context);
        $compDefine = [
            '鼠' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('鼠'))) {
                        return true;
                    }
                }
                return false;
            },
            '牛' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('牛'))) {
                        return true;
                    }
                }
                return false;
            },
            '虎' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('虎'))) {
                        return true;
                    }
                }
                return false;
            },
            '兔' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('兔'))) {
                        return true;
                    }
                }
                return false;
            },
            '龙' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('龙'))) {
                        return true;
                    }
                }
                return false;
            },
            '蛇' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('蛇'))) {
                        return true;
                    }
                }
                return false;
            },
            '马' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('马'))) {
                        return true;
                    }
                }
                return false;
            },
            '羊' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('羊'))) {
                        return true;
                    }
                }
                return false;
            },
            '猴' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('猴'))) {
                        return true;
                    }
                }
                return false;
            },
            '鸡' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('鸡'))) {
                        return true;
                    }
                }
                return false;
            },
            '狗' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('狗'))) {
                        return true;
                    }
                }
                return false;
            },
            '猪' => function ($periodCodeList) use ($site) {
                for ($i = 0; $i <= $site - 1; $i++) {
                    if (in_array($periodCodeList[$i], self::getShengxiao('猪'))) {
                        return true;
                    }

                }
                return false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
                if ($isDouble) {
                    $k = 0;
                    for ($i = 0; $i <= 5; $i++) {
                        if (in_array($context->periodCodeList[$i], self::getShengxiao($v))) {
                            $k++;
                        }
                    }
                    $context->oddsOrigin[$index] = ($context->odds[$index] - 1) * $k + 1;
                }

            }
        }
    }


    /**
     * @param $context
     * 正码生肖
     */
    public static function ResultZzmsx($context) {
        self::SxBaseRand($context, 6, true);
    }

    /**
     * @param $context
     * 正特生肖
     */
    public static function ResultZztsx($context) {
        self::SxBaseRand($context, 7);
    }

    /**
     * @param $context
     * 特码色波 色波
     */
    public static function ResultTtmsb($context) {
        self::ResultBaseBo($context,self::LHF_SB);
    }

    /**
     * @param $context
     * 特码半波
     */
    public static function ResultTtmbb($context) {
        $bb = [
            '红单' => ['01', '07', '13', '19', '23', '29', '35', '45'],
            '红双' => ['02', '08', '12', '18', '24', '30', '34', '40', '46'],
            '红大' => ['05', '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'],
            '红小' => ['01', '02', '07', '08', '12', '13', '18', '19', '23', '24'],
            '蓝单' => ['03', '09', '15', '25', '31', '37', '41', '47'],
            '蓝双' => ['04', '10', '14', '20', '26', '36', '42', '48'],
            '蓝大' => ['25', '26', '31', '36', '37', '41', '42', '47', '48'],
            '蓝小' => ['03', '04', '09', '10', '14', '15', '20'],
            '绿单' => ['05', '11', '17', '21', '27', '33', '39', '43'],
            '绿双' => ['06', '16', '22', '28', '32', '38', '44'],
            '绿大' => ['27', '28', '32', '33', '38', '39', '43', '44'],
            '绿小' => ['05', '06', '11', '16', '17', '21', '22'],
        ];

        self::ResultBaseBanBo($context, $bb);

    }

    public static function ResultBaseBanBo($context, $config) {
        self::_init($context);
        $compDefine = [
            '红单' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[0] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['红单']) ? true : false;
                }
            },
            '红双' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[1] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['红双']) ? true : false;
                }
            },
            '红大' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[2] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['红大']) ? true : false;
                }
            },
            '红小' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[3] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['红小']) ? true : false;
                }
            },
            '蓝单' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[4] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['蓝单']) ? true : false;
                }
            },
            '蓝双' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[5] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['蓝双']) ? true : false;
                }
            },
            '蓝大' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[6] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['蓝大']) ? true : false;
                }
            },
            '蓝小' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[7] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['蓝小']) ? true : false;
                }
            },
            '绿单' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[8] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['绿单']) ? true : false;
                }
            },
            '绿双' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[9] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['绿双']) ? true : false;
                }
            },
            '绿大' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[10] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['绿大']) ? true : false;
                }
            },
            '绿小' => function ($periodCodeList) use ($config, $context) {
                if ($periodCodeList[6] == 49) {
                    $context->oddsOrigin[11] = 1;
                    return true;
                } else {
                    return in_array($periodCodeList[6], $config['绿小']) ? true : false;
                }
            },
        ];
//'红单', '红双', '红大', '红小', '蓝单', '蓝双', '蓝大', '蓝小', '绿单', '绿双', '绿大', '绿小',
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }


    }


    /**
     * @param $context
     * 特码三波
     */
    public static function ResultTmsb($context) {
        $sb = [
            '红大单' => ['29', '35', '45'],
            '红大双' => ['30', '34', '40', '46'],
            '红小单' => ['01', '07', '13', '19', '23'],
            '红小双' => ['02', '08', '12', '18', '24'],
            '蓝大单' => ['25', '31', '37', '41', '47'],
            '蓝大双' => ['26', '36', '42', '48'],
            '蓝小单' => ['03', '09', '15'],
            '蓝小双' => ['04', '10', '14', '20'],
            '绿大单' => ['27', '33', '39', '43', '49'],
            '绿大双' => ['28', '32', '38', '44'],
            '绿小单' => ['05', '11', '17', '21'],
            '绿小双' => ['06', '16', '22'],
        ];
        self::ResultBaseSanBo($context, $sb);
    }


    public static function ResultBaseSanBo($context, $config) {
        self::_init($context);
        //'红大单', '红大双', '红小单', '红小双', '蓝大单', '蓝大双', '蓝小单', '蓝小双', '绿大单', '绿大双', '绿小单', '绿小双',
        $compDefine = [
            '红大单' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['红大单']) ? true : false;
            },
            '红大双' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['红大双']) ? true : false;
            },
            '红小单' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['红小单']) ? true : false;
            },
            '红小双' => function ($periodCodeList) use ($config, $context) {
                return in_array($periodCodeList[6], $config['红小双']) ? true : false;
            },
            '蓝大单' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['蓝大单']) ? true : false;
            },
            '蓝大双' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['蓝大双']) ? true : false;
            },
            '蓝小单' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['蓝小单']) ? true : false;

            },
            '蓝小双' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['蓝小双']) ? true : false;
            },
            '绿大单' => function ($periodCodeList) use ($config, $context) {
                return in_array($periodCodeList[6], $config['绿大单']) ? true : false;
            },
            '绿大双' => function ($periodCodeList) use ($config, $context) {
                return in_array($periodCodeList[6], $config['绿大双']) ? true : false;
            },
            '绿小单' => function ($periodCodeList) use ($config, $context) {
                return in_array($periodCodeList[6], $config['绿小单']) ? true : false;
            },
            '绿小双' => function ($periodCodeList) use ($config, $context) {
                return in_array($periodCodeList[6], $config['绿小双']) ? true : false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }


    }

    /**
     * @param $context
     * @param $config
     */
    public static function ResultBaseBo($context, $config) {
        self::_init($context);
        $compDefine = [
            '红波' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['红波']) ? true : false;
            },
            '绿波' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['绿波']) ? true : false;
            },
            '蓝波' => function ($periodCodeList) use ($config) {
                return in_array($periodCodeList[6], $config['蓝波']) ? true : false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }


    }


    /**
     * @param $context
     * 特码头数
     */
    public static function ResultTtmts($context) {
        self::_init($context);
        //'头0', '头1', '头2', '头3', '头4',
        $compDefine = [
            '头0' => function ($periodCodeList) {
                return substr($periodCodeList[6], 0, 1) == 0 ? true : false;
            },
            '头1' => function ($periodCodeList) {
                return substr($periodCodeList[6], 0, 1) == 1 ? true : false;
            },
            '头2' => function ($periodCodeList) {
                return substr($periodCodeList[6], 0, 1) == 2 ? true : false;
            },
            '头3' => function ($periodCodeList) {
                return substr($periodCodeList[6], 0, 1) == 3 ? true : false;
            },
            '头4' => function ($periodCodeList) {
                return substr($periodCodeList[6], 0, 1) == 4 ? true : false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }

    }


    /**
     * @param $context
     * 特码尾数
     */
    public static function ResultTtmws($context) {
        self::_init($context);
        //'尾0', '尾1', '尾2', '尾3', '尾4', '尾5', '尾6', '尾7', '尾8', '尾9',
        $compDefine = [
            '尾0' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 0 ? true : false;
            },
            '尾1' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 1 ? true : false;
            },
            '尾2' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 2 ? true : false;
            },
            '尾3' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 3 ? true : false;
            },
            '尾4' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 4 ? true : false;
            },
            '尾5' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 5 ? true : false;
            },
            '尾6' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 6 ? true : false;
            },
            '尾7' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 7 ? true : false;
            },
            '尾8' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 8 ? true : false;
            },
            '尾9' => function ($periodCodeList) {
                return substr($periodCodeList[6], 1, 1) == 9 ? true : false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }


    }


    /**
     * @param $context
     * 正码尾数
     */
    public static function ResultZzmws($context) {
        self::_init($context);
        //'尾0', '尾1', '尾2', '尾3', '尾4', '尾5', '尾6', '尾7', '尾8', '尾9',
        $compDefine = [
            '尾0' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 0) {
                        return true;
                    }
                }
                return false;

            },
            '尾1' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 1) {
                        return true;
                    }
                }
                return false;
            },
            '尾2' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 2) {
                        return true;
                    }
                }
                return false;
            },
            '尾3' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 3) {
                        return true;
                    }
                }
                return false;
            },
            '尾4' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 4) {
                        return true;
                    }
                }
                return false;
            },
            '尾5' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 5) {
                        return true;
                    }
                }
                return false;
            },
            '尾6' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 6) {
                        return true;
                    }
                }
                return false;
            },
            '尾7' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 7) {
                        return true;
                    }
                }
                return false;
            },
            '尾8' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 8) {
                        return true;
                    }
                }
                return false;
            },
            '尾9' => function ($periodCodeList) {
                for ($i = 0; $i <= 5; $i++) {
                    if (substr($periodCodeList[$i], 1, 1) == 9) {
                        return true;
                    }
                }
                return false;
            },
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
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }
    }


    //正B定位(双面) '大', '小', '单', '双', '小单', '小双', '大单', '大双','和大','和小','和单','和双','尾大','尾小'

    /**
     * @param $context
     * @param $site
     */
    public static function resultBaseZzw($context, $site) {
        $sb = self::LHF_SB;
        self::_init($context);

        $compDefine = [
            '大' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[0] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] >= 25 ? true : false;
                }
            },
            '小' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[1] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] <= 24 ? true : false;
                }
            },
            '单' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[2] = 1;
                    return true;
                } else {
                    $end = $periodCodeList[$site];
                    return $end % 2 == 1 ? true : false;
                }
            },
            '双' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[3] = 1;
                    return true;
                } else {
                    $end = $periodCodeList[$site];
                    return $end % 2 == 0 ? true : false;
                }
            },
            '大单' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[4] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] >= 25 && $periodCodeList[$site] % 2 == 1 ? true : false;
                }
            },
            '小单' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[5] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] <= 24 && $periodCodeList[$site] % 2 == 1 ? true : false;
                }
            },
            '大双' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[6] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] >= 25 && $periodCodeList[$site] % 2 == 0 ? true : false;
                }
            },
            '小双' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[7] = 1;
                    return true;
                } else {
                    return $periodCodeList[$site] <= 24 && $periodCodeList[$site] % 2 == 0 ? true : false;
                }
            },
            '和大' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[8] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[$site]);
                    if (strlen($period) == 1) {
                        return $period >= 7 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return $number1 + $number2 >= 7 ? true : false;
                    }
                }
            },
            '和小' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[9] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[$site]);
                    if (strlen($period) == 1) {
                        return $period <= 6 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return $number1 + $number2 <= 6 ? true : false;
                    }
                }
            },
            '和单' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[10] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[$site]);
                    if (strlen($period) == 1) {
                        return $period % 2 == 1 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return ($number1 + $number2) % 2 == 1 ? true : false;
                    }
                }
            },
            '和双' => function ($periodCodeList) use ($site, $context) {
                if ($periodCodeList[$site] == 49) {
                    $context->oddsOrigin[11] = 1;
                    return true;
                } else {
                    $period = intval($periodCodeList[$site]);
                    if (strlen($period) == 1) {
                        return $period % 2 == 0 ? true : false;
                    } else {
                        $num1 = str_split($period);
                        $number1 = $num1[count($num1) - 1];
                        $number2 = $num1[count($num1) - 2];
                        return ($number1 + $number2) % 2 == 0 ? true : false;
                    }
                }
            },
            '尾大' => function ($periodCodeList) use ($site) {
                $period = intval($periodCodeList[$site]);
                if (strlen($period) == 1) {
                    return $period >= 5 ? true : false;
                } else {
                    $num1 = str_split($period);
                    $number1 = $num1[count($num1) - 1];
                    return $number1 >= 5 ? true : false;
                }
            },
            '尾小' => function ($periodCodeList) use ($site) {
                $period = intval($periodCodeList[$site]);
                if (strlen($period) == 1) {
                    return $period <= 4 ? true : false;
                } else {
                    $num1 = str_split($period);
                    $number1 = $num1[count($num1) - 1];//个位
                    //$number2 = $num1[count($num1) - 2];//十位
                    return $number1 <= 4 ? true : false;
                }
            },
            '红波' => function ($periodCodeList) use ($site, $sb) {
                $period = $periodCodeList[$site];
                return in_array($period, $sb['红波']) ? true : false;
            },
            '蓝波' => function ($periodCodeList) use ($site, $sb) {
                $period = $periodCodeList[$site];
                return in_array($period, $sb['蓝波']) ? true : false;
            },
            '绿波' => function ($periodCodeList) use ($site, $sb) {
                $period = $periodCodeList[$site];
                return in_array($period, $sb['绿波']) ? true : false;
            },
        ];

        // $getText = function ($v) use ($compDefine) {
        //     $texts = [];
        //     foreach ($compDefine as $key => $func) {
        //         if ($func($v)) {
        //             $texts[] = $key;
        //         }
        //     }
        //     return $texts;
        // };

        // $texts = $getText($context->periodCodeList);
        // echo PHP_EOL;
        foreach ($context->playNumberList[0] as $v) {
            $funds = $compDefine[$v];
            // echo $v, 'p ' , PHP_EOL;
            if ($funds($context->periodCodeList)) {
                // echo $v, 's ' , PHP_EOL;
                $index = array_search($v, $context->lotteryConfig['standardOddsDesc']);
                $context->isWin[$index] = true;
                $context->winBetCount[$index]++;
            }
        }

    }


    /**
     * @param $context
     * 正码一 正B定位(双面)
     */
    public static function ResultZzmy($context) {
        self::resultBaseZzw($context, 0);
    }

    /**
     * @param $context
     * 正码二 正B定位(双面)
     */
    public static function ResultZzme($context) {
        self::resultBaseZzw($context, 1);
    }

    /**
     * @param $context
     * 正码三 正B定位(双面)
     */
    public static function ResultZzms($context) {
        self::resultBaseZzw($context, 2);
    }

    /**
     * @param $context
     * 正码四 正B定位(双面)
     */
    public static function ResultZzmsi($context) {
        self::resultBaseZzw($context, 3);
    }

    /**
     * @param $context
     * 正码五 正B定位(双面)
     */
    public static function ResultZzmw($context) {
        self::resultBaseZzw($context, 4);
    }

    /**
     * @param $context
     * 正码六 正B定位(双面)
     */
    public static function ResultZzml($context) {
        self::resultBaseZzw($context, 5);
    }

    /**
     * @param $context
     * 三中二（中二，中三）
     */
    public static function ResultSze($context){
        self::_init($context);
        $zm_list = array_slice($context->periodCodeList, 0, 6);//获取正码
        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        foreach ($matrix as $playNumbers) {
            $succCount = 0;
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            foreach ($playNumbers as $playNumber) {
                if (in_array($playNumber, $zm_list)) {
                    $succCount++;//计算号码出现的次数
                }
            }
            if ($succCount == 2) {//中二
                $context->isWin[0] = true;
                $context->winBetCount[0]++;//中二的注数
            }
            if ($succCount == 3){//中三
                $context->isWin[1] = true;
                $context->winBetCount[1]++;//中三的注数
            }
        }
    }

    /**
     * @param $context
     * 三全中
     */
    public static function ResultSqz($context){
        self::_init($context);
        $zm_list = array_slice($context->periodCodeList, 0, 6);//获取正码
        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        foreach ($matrix as $playNumbers) {
            $succCount = 0;
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            foreach ($playNumbers as $playNumber) {
                if (in_array($playNumber, $zm_list)) {
                    $succCount++;//计算号码出现的次数
                }
            }
            if ($succCount == 3){//中三
                $context->isWin[0] = true;
                $context->winBetCount[0]++;//中三的注数
            }
        }
    }

    /**
     * @param $context
     * 二全中
     */
    public static function ResultEqz($context){
        self::_init($context);
        $zm_list = array_slice($context->periodCodeList, 0, 6);//获取正码
        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        foreach ($matrix as $playNumbers) {
            $succCount = 0;
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            foreach ($playNumbers as $playNumber) {
                if (in_array($playNumber, $zm_list)) {
                    $succCount++;//计算号码出现的次数
                }
            }
            if ($succCount == 2){//二全中
                $context->isWin[0] = true;
                $context->winBetCount[0]++;//注数
            }
        }
    }

    /**
     * @param $context
     * 二中特（中二，中特）
     */
    public static function ResultEzt($context){
        self::_init($context);
        $zm_list = array_slice($context->periodCodeList, 0, 6);//获取正码
        $spe_code = $context->periodCodeList[6];//特码
        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        foreach ($matrix as $playNumbers) {
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            if (in_array($playNumbers[0], $zm_list) && in_array($playNumbers[1], $zm_list)) {//中二个正码
                $context->isWin[0] = true;
                $context->winBetCount[0]++;//中二的注数
            }
            if ( ($playNumbers[0] == $spe_code && in_array($playNumbers[1], $zm_list)) || ($playNumbers[1] == $spe_code && in_array($playNumbers[0], $zm_list)) ){//中特
                $context->isWin[1] = true;
                $context->winBetCount[1]++;//中特的注数
            }
        }
    }

    /**
     * @param $context
     * 特串
     */
    public static function ResultTec($context){
        self::_init($context);
        $zm_list = array_slice($context->periodCodeList, 0, 6);//获取正码
        $spe_code = $context->periodCodeList[6];//特码
        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        foreach ($matrix as $playNumbers) {
            $playNumbers = !is_array($playNumbers) ? explode(',', $playNumbers) : $playNumbers;
            if ( ($playNumbers[0] == $spe_code && in_array($playNumbers[1], $zm_list)) || ($playNumbers[1] == $spe_code && in_array($playNumbers[0], $zm_list)) ){//特串
                $context->isWin[0] = true;
                $context->winBetCount[0]++;//中特的注数
            }
        }
    }

    /**
     * 连肖
     * @param $context
     * @param $length 几连肖，2，3，4，5
     */
    public static function ResultLianXiao($context, $length){
        self::_init($context);
        foreach ($context->periodCodeList as $pos => $code) {
            $lhc = self::getNumberInfo($code);
            $periodSx[] = $lhc['sx'];
        }
//        $matrix = isset($context->matrix) ? $context->matrix : $context->playNumberList[0];//获取矩阵
        // 计算当前投注生肖在7个开奖生肖的命中次数
        $playSx = [];
        foreach ($context->playNumberList[0] as $key=>$val){
            if(in_array($val, $periodSx)){
                $playSx[] = $val;
            }
        }
        if(count($playSx) < $length){//命中生肖次数不足，则没有中奖
            return;
        }
        $com_list = getCombinationToString($playSx, $length);//中奖生肖组合
        $sys_odds = array_combine($context->lotteryConfig['standardOddsDesc'], $context->odds);
        $context->oddsList = [];
        $context->winSx = [];
        foreach ($com_list as $k=>$sx_str){
            $context->isWin[$k] = true;//已中奖
            $context->winBetCount[$k]++;//中奖注数
            $context->oddsList[$k] = self::get_odds($sx_str, $sys_odds);//中奖生肖对应的赔率
            $context->winSx[$sx_str] = $context->winBetCount[$k];//中奖生肖
        }
    }

    //获取组合的实际的最小赔率
    protected static function get_odds($sx_str, $sys_odds){
        $sx = explode(",", $sx_str);
        $min_odd = $sys_odds[$sx[0]];
        foreach ($sx as $key=>$val){
            $tmp_odd = $sys_odds[$val];
            if($min_odd > $tmp_odd){
                $min_odd = $tmp_odd;
            }
        }
        return $min_odd;
    }
}