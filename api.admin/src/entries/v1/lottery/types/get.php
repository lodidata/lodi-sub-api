<?php

//use Logic\Admin\Active as activeLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '';
    const DESCRIPTION = '';
    
    const QUERY = [
        'id' => 'int(required)',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        return $this->getLotteryList();
    }

    protected function getLotteryList() {

        $params = $this->request->getParams();

        $query = DB::table('lottery')
                   ->select(DB::raw('id, name, state, pic as img, h5_pic as h5_img, type, open_type, pc_open_type, pid, alias, switch, code, sort, start_delay, buy_f_img, buy_c_img, open_img, FIND_IN_SET(\'standard\', switch) AS is_standard, FIND_IN_SET(\'fast\', switch) AS is_fast, all_bet_max, per_bet_max'))
                   ->where('pid', '<>', 0)
                   ->where('id', '<>', 44);

        $query = isset($params['lottery_pid']) && !empty($params['lottery_pid']) ? $query->where('pid', $params['lottery_pid']) : $query;

        $res = $query->orderBy('sort')
                     ->get()
                     ->toArray();

        if (!$res) {
            return [];
        }

        return $res;
    }
};
