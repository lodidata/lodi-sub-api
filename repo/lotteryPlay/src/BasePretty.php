<?php

namespace LotteryPlay;

abstract class BasePretty
{

    public static function getPretty($ch, $playNumber,$flag= false,$vv)
    {

        if ($flag){
            $output = [];
            $isDwd = true;
            if(strstr($ch[0],'vs1')) {
                //龙虎斗特殊处理
                foreach ($ch as $key => $val) {
                    $playNumberArr = explode('|', $playNumber);
                    if (isset($playNumberArr[$key])) {
                        if (strstr($playNumberArr[$key], '|')) {
                            $value = explode('|', $playNumberArr[$key]);
                        } else {
                            $value = trim($playNumberArr[$key], ',');
                        }
                    } else {
                        $value = "";
                    }
                    if (!empty($value)) {
                        $temp['title'] = $val;
                        if(is_array($value)){
                            $temp['value'] = $value;
                        }else{
                            $temp['value'] = [$value];
                        }
                        $output[] = $temp;
                    }
                }
                return $output;
            }
            foreach ($vv ?? [] as $k=>$v){
                if($v != $vv[0]){
                    $isDwd = false;
                    break;
                }
            }
                if(strstr($playNumber,'vs')){
                    $isDwd = true;
                }
                if($isDwd){
                    foreach ($ch as $key => $val) {
                        $playNumberArr = explode(',',$playNumber);
                        if(isset($playNumberArr[$key])){
                            if(strstr($playNumberArr[$key],'|')){
                                $value = explode('|',$playNumberArr[$key]);
                            }else {
                                $value = trim($playNumberArr[$key], ',');
                            }
                        }else{
                            $value = "";
                        }
                        if ($value !== "") {
                            $temp['title'] = $val;
                            if(is_array($value)){
                                $temp['value'] = $value;
                            }else{
                                $temp['value'] = [$value];
                            }
                            $output[] = $temp;
                        }
                    }
                    return $output;
                }
                foreach ($ch as $key => $val) {
                    $value = array_filter($vv[$key], function ($i) use ($playNumber,$key,$isDwd) {
                        if(intval($i).'' == $i) { //说明是数字
                            if ($playNumber == $i) {
                                return $i;
                            }
                            if(strpos($playNumber,'|') !== false) {
                                return mb_strpos('x' . $playNumber, '|'.$i);
                            }
                        }
                        return mb_strpos('x' . $playNumber, $i);
                    });
                    if (!empty($value)) {
                        $temp['title'] = $val;
                        $temp['value'] = array_values($value);
                        $output[] = $temp;
                    }
                }

        }else{
            $playNumbers = explode(',', $playNumber);
            $output = [];
            foreach ($playNumbers as $place => $pn) {
                if ($pn!=""){
                    $pn_arr = explode('|', $pn);
                    //$pn_arr[0] = '#' . $pn_arr[0];
                    //array_unshift($pn_arr,$ch[$place]);
                    $temp['title'] = $ch[$place];
                    $temp['value'] = array_values($pn_arr);
                    $output[] = $temp;
                }
            }
        }
        return $output;
    }
}