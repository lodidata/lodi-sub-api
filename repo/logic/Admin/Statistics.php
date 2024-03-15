<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/7
 * Time: 10:21
 */

namespace Logic\Admin;


class Statistics extends \Logic\Logic
{
    public function __construct($ci)
    {
        parent::__construct($ci);

    }


    /*
     *  最近天数日期显示 当前时间开始计算
     * auth:hu
     * param int  days  天数
     * **/
    public function lately_day($days){

        $daysarr=[];
        for ($i=0;$i<$days;$i++){

            $t = time()+3600*8;//这里和标准时间相差8小时需要补足
            $tget = $t-3600*24*$i;//比如5天前的时间
            $daysarr[$i]=date("Y-m-d",$tget);
        }
        sort($daysarr);
        return $daysarr;
    }
}