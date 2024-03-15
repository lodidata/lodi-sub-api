<?php 

namespace LotteryPlay;

abstract class BaseFormat {
    /**
     * 开奖结果默认格式化方法
     * @param  [type]  $context           [description]
     * @param  integer $playNumberLength  [description]
     * @param  integer $everyNumberLength [description]
     * @return [type]                     [description]
     */
    public static function periodCodeFormat($context, $playNumberLength = 5, $sort= '') {
        $rules = [
            5 => '/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/',
            3 => '/^([0-9]{1}),([0-9]{1}),([0-9]{1})$/',
        ];

        $code = $context->periodCode;
        if (empty($code)) {
            $context->stopNext()->setReason('开奖号码不能为空');
            return;
        }

        $codes = [];
        foreach (explode(',', $context->periodCode) as $k => $v) {
            $codes[] = intval($v);
        }
        $code = $context->periodCode = join(',', $codes);
        preg_match($rules[$playNumberLength], $context->periodCode, $matches);
        if (!isset($matches[0])) {
            $context->stopNext()->setReason('开奖号码格式不正确');
        }

        $context->periodCodeList = explode(',', $context->periodCode);
        if (!empty($sort)) {
            if ($sort == 'desc') {
                arsort($context->periodCodeList);
            } else {
                asort($context->periodCodeList);
            }
        }
    }

    /**
     * 开奖结果默认格式化方法
     * @param  [type]  $context           [description]
     * @param  integer $playNumberLength  [description]
     * @param  integer $everyNumberLength [description]
     * @return [type]                     [description]
     */
    public static function periodCodeFormatByRange($context, $range = [1,2,3,4,5,6,7,8,9,10,11], $length = 5, $unique = true, $sort = '') {
        if (empty($context->periodCode)) {
            return $context->stopNext()->setReason('开奖号码不能为空');
        }

        $codes = [];
        foreach (explode(',', $context->periodCode) as $k => $v) {
            if (!in_array(intval($v), $range)) {
                return $context->stopNext()->setReason('开奖号码错误');
            }
            $codes[] = intval($v);
        }
        if ($unique) {
            $codes = array_unique($codes);
        }

        if ($length != count($codes)) {
            return $context->stopNext()->setReason('开奖号码格式错误');
        }
        $context->periodCode = join(',', $codes);

        $context->periodCodeList = explode(',', $context->periodCode);
        if (!empty($sort)) {
            if ($sort == 'desc') {
                arsort($context->periodCodeList);
            } else {
                asort($context->periodCodeList);
            }
        }
    }

    /**
     * 默认格式化方法
     * @param  [type]  $context           [description]
     * @param  integer $playNumberLength  [description]
     * @param  integer $everyNumberLength [description]
     * @return [type]                     [description]
     */
    public static function format($context, $playNumberLength = 3, $everyNumberLength = 0) {
        $context->playNumberLength = $playNumberLength;
        $rules = [
            5 => '/^(([0-9|]){1,19}),([0-9|]{1,19}),([0-9|]{1,19}),([0-9|]{1,19}),([0-9|]{1,19})$/',
            4 => '/^(([0-9|]){1,19}),([0-9|]{1,19}),([0-9|]{1,19}),([0-9|]{1,19})$/',
            3 => '/^(([0-9|]){1,19}),([0-9|]{1,19}),([0-9|]{1,19})$/',
            2 => '/^(([0-9|]){1,19}),([0-9|]{1,19})$/',
            1 => '/^(([0-9|]){1,19})$/',
            0 => '',
        ];

        if (self::checkCode($context, $context->playNumber)) {
            if (!empty($rules[$playNumberLength])) {
                preg_match($rules[$playNumberLength], $context->playNumber, $matches);
                if (!isset($matches[0])) {
                    return $context->stopNext()->setReason('下注号码格式不正确');
                }
            }

            $context->playNumberList = [];
            foreach (explode(',', $context->playNumber) as $key => $value) {
                $lists = explode('|', $value);
                if (self::checkNumbers($context, $lists)) {
                    $context->playNumberList[$key] = array_unique($lists);
                } 

                if ($everyNumberLength != 0 && count($lists) < $everyNumberLength) {
                    return $context->stopNext()->setReason('下注号码数量不正确');
                }
            }
        } 
    }

    /**
     * 利用赔率描述设定限制值
     * @param  [type] $context [description]
     * @return [type]          [description]
     */
    public static function formatByOddsDesc($context) {
        
        if (self::checkCode($context, $context->playNumber)) {
            $lists = array_unique(explode('|', $context->playNumber));
            foreach ($lists as $key => $value) {
                if (!in_array($value, $context->lotteryConfig['standardOddsDesc'])) {
                    return $context->stopNext()->setReason('下注数据不正确');
                }
            }
            $context->playNumberList[0] = $lists;
        }
    }

    /**
     * 利用赔率描述设定限制值 （同时支持使用|和,相隔）
     * @param  [type] $context [description]
     * @return [type]          [description]
     */
    public static function formatByOddsDesc2($context, $field = 'standardOddsDesc') {
        // $a = '万千龙,,万千虎|a,万千狗';
        // $data = preg_split("/[|,]+/", $a, -1, PREG_SPLIT_NO_EMPTY);
        // print_r($data);exit;
        if (self::checkCode($context, $context->playNumber)) {
            $lists = array_unique(preg_split("/[|,]+/", $context->playNumber, -1, PREG_SPLIT_NO_EMPTY));
            foreach ($lists as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                if (!in_array($value, $context->lotteryConfig[$field])) {
                    return $context->stopNext()->setReason('下注数据不正确');
                }
            }
            $context->playNumberList[0] = $lists;
            $context->playNumber = join('|', $lists);
        }
    }

    /**
     * 利用配置设定限制值
     * @param  [type] $context [description]
     * @return [type]          [description]
     */
    public static function formatByConfKey($context, $field = 'standardOddsDescAlias', $playNumberLength = 1, $everyNumberLength = 0) {
        $context->playNumberLength = $playNumberLength;
        if (self::checkCode($context, $context->playNumber)) {
            $context->playNumberList = [];
            foreach (explode(',', $context->playNumber) as $key => $value) {
                // $lists = array_unique(explode('|', $value));
                $lists = array_unique(preg_split("/[|,]+/", $value, -1, PREG_SPLIT_NO_EMPTY));
                foreach ($lists ?? [] as $v) {
                    if (!in_array($v, $context->lotteryConfig[$field])) {
                        return $context->stopNext()->setReason('下注数据不正确');
                    }
                }
                
                if ($everyNumberLength != 0 && count($lists) < $everyNumberLength) {
                    return $context->stopNext()->setReason('下注号码数量不正确');
                }

                $context->playNumberList[$key] = $lists;
            }
        } 
    }

    /**
     * 三星（豹子、顺子、对子）
     * @param  [type] $context [description]
     * @return [type]          [description]
     */
    public static function format3bsd($context, $type = 0) {
        $rules = [
            0 => '/^([(豹子通选)(000)(111)(222)(333)(444)(555)(666)(777)(888)(999)|]{1,})$/',
            1 => '/^([(顺子通选)(012)(123)(234)(345)(456)(567)(678)(789)(890)(901)|]{1,})$/',
            2 => '/^([(对子通选)(00)(11)(22)(33)(44)(55)(66)(77)(88)(99)|]{1,})$/',
        ];

        if (self::checkCode($context, $context->playNumber)) {
            preg_match($rules[$type], $context->playNumber, $matches);
            if (!isset($matches[0])) {
                return $context->stopNext()->setReason('下注号码格式不正确#0');
            }
            $context->playNumberList[0] = array_unique(explode('|', $context->playNumber));
        }
    }

    public static function checkNumbers($context, $lists, $max = 9) {
        if (empty($lists)) {
            $context->stopNext()->setReason('下注号码格式不正确#1');
            return false;
        }

        foreach ($lists as $k => $v) {
            if ($v === '') {
                $context->stopNext()->setReason('下注号码格式不正确#2');
                return false;
            }

            if ($v > $max) {
                $context->stopNext()->setReason('下注号码格式不正确#3');
                return false;
            }
        }
        return true;
    }

    // 直选和值
    public static function formatZxhz($context, $max = 27, $playNumberLength = 1, $everyNumberLength = 0) {
        $context->playNumberLength = $playNumberLength;
        $rules = [
            1 => '/^(([0-9|]){1,})$/',
        ];

        if (self::checkCode($context, $context->playNumber)) {
            preg_match($rules[$playNumberLength], $context->playNumber, $matches);
            if (!isset($matches[0])) {
                return $context->stopNext()->setReason('下注号码格式不正确');
            }

            $context->playNumberList = [];
            foreach (explode(',', $context->playNumber) as $key => $value) {
                $lists = explode('|', $value);
                if (self::checkNumbers($context, $lists, $max)) {
                    $context->playNumberList[$key] = array_unique($lists);
                }

                if ($everyNumberLength != 0 && count($lists) < $everyNumberLength) {
                    return $context->stopNext()->setReason('下注号码数量不正确');
                }

                if ($key + 1 > $playNumberLength) {
                    return $context->stopNext()->setReason('下注号码格式不正确');
                }
            }
        } 
    }

    /**
     * 不定位、单球
     * @param  [type]  $context          [description]
     * @param  integer $playNumberLength [description]
     * @return [type]                    [description]
     */
    public static function formatDwd($context, $playNumberLength = 5, $range = [0,1,2,3,4,5,6,7,8,9], $everyNumberLength = 0, $mutex = false, $everyNumberLengthOR = false) {
        
        if (self::checkCode($context, $context->playNumber)) {
            $context->playNumberList = [];
            $list = explode(',', $context->playNumber);
            if (is_array($playNumberLength) && !in_array(count($list), $playNumberLength)) {
                return $context->stopNext()->setReason('下注号码格式不正确#1 '.count($list).print_r($list, true));
            } else if (is_array($playNumberLength)) {
                $context->playNumberLength = $playNumberLength = count($list);
            } else {
                $context->playNumberLength = $playNumberLength;
            }

            foreach ($list as $key => $value) {

                if ($value !== '') {
                    $lists = explode('|', $value);
                    foreach ($lists as $k => $v) {
                        if ($v !== '' && !in_array($v, $range)) {
                            return $context->stopNext()->setReason('下注号码格式不正确');
                        }
                    }
                    $lists = array_unique($lists);

                    // $curLength = is_array($everyNumberLength) ? $everyNumberLength[$key] : $everyNumberLength;
                    // if ($curLength != 0 && count($lists) < $curLength) {
                    //     return $context->stopNext()->setReason('下注号码数量不正确'.($key+1).':'.$curLength.print_r($everyNumberLength, true));
                    // }

                    $context->playNumberList[$key] = $lists;
                } else {
                    $context->playNumberList[$key] = [];
                }
            }

            // 互斥检查
            if ($mutex) {
                $allNumber = [];
                foreach ($context->playNumberList as $val) {
                    foreach ($val as $v) {
                        if (in_array($v, $allNumber)) {
                            return $context->stopNext()->setReason('号码互斥检查失败');
                        }
                    }
                    $allNumber = array_merge($allNumber, $val);
                }
            }

            for ($i = 0; $i < $playNumberLength; $i++) {
                $lists = isset($context->playNumberList[$i]) ? $context->playNumberList[$i] : [];
                $curLength = is_array($everyNumberLength) ? $everyNumberLength[$i] : $everyNumberLength;

                if (!$everyNumberLengthOR) {
                    if ($curLength != 0 && count($lists) < $curLength) {
                        return $context->stopNext()->setReason('下注号码数量不正确@1');
                    }
                } else {
                    $curLength = isset($everyNumberLength[0]) ? $everyNumberLength[0] : $everyNumberLength;
                    $curLength2 = isset($everyNumberLength[1]) ? $everyNumberLength[1] : $everyNumberLength;
                    if ( ($curLength != 0 && count($lists) < $curLength) && ($curLength2 != 0 && count($lists) < $curLength2) ) {
                        return $context->stopNext()->setReason('下注号码数量不正确@2');
                    }
                }
            }
            
            if (count($context->playNumberList) != $playNumberLength) {
                return $context->stopNext()->setReason('下注号码格式不正确#2 '.count($context->playNumberList).'-'.$playNumberLength);
            }
        }
    }

    /**
     * 不同号
     * @param  [type]  $context          [description]
     * @param  integer $playNumberLength [description]
     * @return [type]                    [description]
     */
    public static function formatDwdRange($context, $playNumberLength = 2, $everyNumberLength = 0, $range = [[11,22,33,44,55,66], [1,2,3,4,5,6]]) {
        $context->playNumberLength = $playNumberLength;
        if (self::checkCode($context, $context->playNumber)) {
            $context->playNumberList = [];
            foreach (explode(',', $context->playNumber) as $key => $value) {
                if ($value !== '') {
                    $lists = explode('|', $value);
                    foreach ($lists as $k => $v) {
                        if ($v !== '' && !in_array($v, $range[$key])) {
                            return $context->stopNext()->setReason('下注号码格式不正确');
                        }
                    }
                    $lists = array_unique($lists);
                    if ($everyNumberLength != 0 && count($lists) < $everyNumberLength) {
                        return $context->stopNext()->setReason('下注号码数量不正确');
                    }

                    $context->playNumberList[$key] = $lists;
                } else {
                    if ($everyNumberLength != 0) {
                        return $context->stopNext()->setReason('下注号码数量不正确');
                    }
                    $context->playNumberList[$key] = [];
                }
            }

            if (count($context->playNumberList) != $playNumberLength) {
                return $context->stopNext()->setReason('下注号码格式不正确#2 '.count($context->playNumberList).'-'.$playNumberLength);
            }
        }
    }
    
    public static function checkCode($context, $code) {
        if (trim($context->playNumber) === '') {
            $context->stopNext()->setReason('下注号码不能为空');
            return false;
        }
        return true;
    }
}