<?php

// namespace LotteryPlay\Functions;
// namespace LotteryPlay;
/**
 * 对比两个数组的值是否相等(按顺序)
 * @param  [type]  $list1 [description]
 * @param  [type]  $list2 [description]
 * @param  integer $start [description]
 * @param  integer $end   [description]
 * @return [type]         [description]
 */
function arrayValueEquals($list1, $list2, $start = 0, $end = 4) {
    for ($i = $start; $i < $end; $i++) {
        if ($list1[$i] != $list2[$i]) {
            return false;
        }
    }
    return true;
}

/**
 * 对比两个数组的值是否相等(不按顺序)
 * @param  [type]  $list1 [description]
 * @param  [type]  $list2 [description]
 * @param  integer $start [description]
 * @param  integer $end   [description]
 * @return [type]         [description]
 */
function arrayValueEquals2($list1, $list2) {
    if (count($list1) == count($list2) && empty(array_diff($list1, $list2))) {
        return true;
    }
    return false;
}

/**
 * 数组转换成矩阵
 * @param  [type]  $playNumbers [description]
 * @param  integer $index       [description]
 * @return [type]               [description]
 */
function matrix($playNumbers, $index = 0) {
    $list = [];        
    if (is_array($playNumbers[$index])) {
        foreach ($playNumbers[$index] as $val) {
            if (isset($playNumbers[$index + 1])) {
                $res = matrix($playNumbers, $index + 1);
                if (!empty($res)) {
                    foreach ($res as $v) {
                        $list[] = array_merge([$val], (is_array($v) ? $v : [$v]));
                    }
                } else {
                    $list[] = [$val];
                }
            } else {
                $list[] = [$val];
            }
        }
    }
    return $list;
}

/**
 * 数组转换成矩阵(前后排重)
 * @param  [type]  $playNumbers [description]
 * @param  integer $index       [description]
 * @return [type]               [description]
 */
function matrix2($playNumbers, $index = 0) {
    $list = [];        
    if (is_array($playNumbers[$index])) {
        foreach ($playNumbers[$index] as $val) {
            if (isset($playNumbers[$index + 1])) {
                $res = matrix2($playNumbers, $index + 1);
                if (!empty($res)) {
                    foreach ($res as $v) {
                        if (in_array($val, (is_array($v) ? $v : [$v]))) {
                            // no do something
                        } else {
                            $list[] = array_merge([$val], (is_array($v) ? $v : [$v]));
                        }
                    }
                } else {
                    $list[] = [$val];
                }
            } else {
                $list[] = [$val];
            }
        }
    }
    return $list;
}

function filterArrayByLength(&$list, $length) {
    foreach ($list as $key => $val) {
        if (count($val) != $length) {
            unset($list[$key]);
        }
    }
}

/**
 * 从M取N个数
 * @param  [type] $arr [description]
 * @param  [type] $m   [description]
 * @return [type]      [description]
 */
function getCombinationToString($arr,$m)
{
    $result = array();
    if ($m ==1)
    {
        return $arr;
    }

    if ($m == count($arr))
    {
        $result[] = implode(',' , $arr);
        return $result;
    }

    $temp_firstelement = $arr[0];
    unset($arr[0]);
    $arr = array_values($arr);
    $temp_list1 = getCombinationToString($arr, ($m-1));

    foreach ($temp_list1 as $s)
    {
        $s = $temp_firstelement.','.$s;
        $result[] = $s;
    }
    unset($temp_list1);

    $temp_list2 = getCombinationToString($arr, $m);
    foreach ($temp_list2 as $s)
    {
        $result[] = $s;
    }
    unset($temp_list2);

    return $result;
}