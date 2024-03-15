<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/6/4
 * Time: 15:27
 */

use Model\Lottery;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '彩票设定';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null) {
        if (is_numeric($id)) {
            return $this->detail($id);
        }

        $lottery = DB::table('lottery')
            ->selectRaw('id,name,pid,type')
            ->whereRaw("FIND_IN_SET('enabled', state)")
            ->where('pid', 0)
            ->get()
            ->toArray();

        foreach ($lottery as &$value) {
            $value->lottery = DB::table('lottery')
                ->selectRaw('id,name,type,open_type, pid,sort,all_bet_max,per_bet_max')
                ->whereRaw("FIND_IN_SET('enabled', state)")
                ->where('pid', $value->id)
                ->orderBy('sort')
                ->get()
                ->toArray();

            foreach ($value->lottery as &$v) {
                $v->halls = DB::table('hall')
                    ->selectRaw('id,hall_name,hall_level,min_bet,max_bet')
                    ->where('lottery_id', $v->id)
                    ->where('hall_level', '!=',4)
                    ->get()
                    ->toArray();
            }
        }

        return $lottery;
    }

    public function detail($id) {
        $lottery = DB::table('lottery')
            ->selectRaw('id ,name, type, open_type, pid, sort, all_bet_max, per_bet_max')
            ->where('id', $id)
            ->first();

        if (!$lottery) {
            return false;
        }

        $lottery->halls = DB::table('hall')
            ->selectRaw('id, hall_name, hall_level, min_bet')
            ->where('lottery_id', $lottery->id)
            ->get()
            ->toArray();

        return [$lottery];
    }
};