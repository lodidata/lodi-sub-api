<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class LotteryChase extends \Illuminate\Database\Eloquent\Model {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'lottery_chase';


    public static function getById($id){

        return self::find($id);

    }
    public function getChaseTypeAttribute($value)
    {

        $arr = ['','中奖不停止', '中奖停止'];
        return $arr[$value];
    }

}