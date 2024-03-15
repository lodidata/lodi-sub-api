<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 18:29
 */

namespace Model;

class Lottery extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery';

    public static function getAllLottery() {
        global $app;
        $db = $app->getContainer()->db->getConnection();
        return Lottery::select([
            'id', 'name', 'state', 'pic as img', 'h5_pic as h5_img', 'type',
            'open_type', 'pc_open_type', 'pid', 'alias', 'switch', 'code',
            'sort', 'start_delay', 'buy_f_img', 'buy_c_img', 'open_img',
            $db->raw("FIND_IN_SET('standard',state) AND FIND_IN_SET('standard',switch) AS is_standard"),
            $db->raw("FIND_IN_SET('fast',state) AND FIND_IN_SET('fast',switch) AS is_fast"),
            'all_bet_max',
            'per_bet_max'
        ])->whereRaw('FIND_IN_SET("enabled",state)')->orderBy('sort')->get()->toArray();
        //->where('pid', '!=', 0)
    }

    /**
     * 获取所有的子彩种 不包括一级
     */
    public static function getAllChildLottery() {
        return \DB::table('lottery as l')->select([
            'l.id', 'l.name', 'l.pid', 'l.index_f_img as img', 'l.alias', 'l.code'

        ])->where('l.pid', '!=', 0)
            ->whereRaw('FIND_IN_SET("enabled",state)')
            ->orderBy('l.sort')->get()->toArray();
    }

}