<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model\Admin;
use DB;
use Utils\Utils;

class HotLottery extends LogicModel {

    protected $table = 'hot_lottery';
    public $timestamps = false;


    //批量更新
    public function updateBatch($multipleData = [])
    {
       $res=Utils::updateBatch($multipleData,'hot_lottery');
       return $res;
    }


    public function getTypeAttribute($value)
    {
        $arr = ['chat'=>'房间模式','standard'=>'传统模式'];
        return isset($arr[$value]) ? $arr[$value] : $value;
    }
}