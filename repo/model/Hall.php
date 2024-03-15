<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;

class Hall extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'hall';

    public static function getHallByLotteryAndLevel($lottery_id,$level)
    {
        return Hall::select([
            'id', 'lottery_id', 'hall_level','type',
        ])->where('lottery_id', '=', $lottery_id)->where('hall_level', '=', $level)->get()->toArray();
    }
}