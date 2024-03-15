<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController {
    const TITLE = '大厅列表';
    const QUERY = [
        'lottery_id' => 'integer() #彩种ID',
        'hall_id'    => 'integer() #厅ID',
        'play_id'    => 'integer() #类型'
    ];
    const PARAMS = [];
    const SCHEMAS = [
            [
                'id'           => 'int() #序号',
                'name'         => 'string() #玩法名称',
                'pid'          => 'int() #上级玩法',
                'alias'        => 'string() #别名',
                'lottery_type' => 'string() #游戏类型',
                'halls'        => [
                    [
                        'id'         => 'int() #厅id',
                        'hall_level' => 'int() #厅类别id',
                        'hall_name'  => 'string() #厅名称'
                    ]
                ]
            ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = null) {

        if (is_numeric($id)) {
            return $this->detail($id);
        }

        (new BaseValidate([
                'type' => 'require|in:base,rebate',
            ]
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();

        $type = $params['type'];

        $method = $type . 'Action';

        if (!method_exists($this, $method)) {
            return [];
        }

        return $this->{$method}($params);
    }

    public function baseAction($params = []) {
        if (!isset($params['lottery_id']) && empty($params['lottery_id'])) {
            return $this->lang->set(10416);
        }

        $lottery = DB::table('lottery')
            ->find($params['lottery_id']);

        if (!$lottery) {
            return $this->lang->set(10015);
        }

        $halls = DB::table('hall')
            ->selectRaw('id,rebet_desc,hall_name,hall_level,min_balance,rebot_list,rebot_min,rebot_max')
            ->where('lottery_id', $params['lottery_id'])
            ->where('hall_level', '<>', 6)
            ->where('hall_level', '<>', 4)
            ->get()
            ->toArray();

        foreach ($halls as $v) {
            $v->room = DB::table('room')
                ->selectRaw('id,room_name,number')
                ->where('hall_id', $v->id)
                ->get()
                ->toArray();
        }

        return $halls;
    }

    public function rebateAction($params = []) {
        $lotteryConfig = new \Logic\Set\SystemConfig($this->ci);
        $lottery = DB::table('lottery')
            ->selectRaw('id,name,pid,alias')
            ->where('id', '<>', 44)
            ->where('id', '<>', 1)
            ->where('pid', 0)
            ->get()
            ->toArray();

        $lotteryAlias = $lotteryConfig->lotteryConfig()['alias'];

        foreach ($lottery as &$v) {
            $v->lottery_type = $lotteryType = $lotteryAlias[$v->alias];

            $firstLotteryId = DB::table('lottery')
                ->where('pid', $v->id)
                ->limit(1)
                ->value('id');

            $v->halls = DB::table('hall')
                ->selectRaw('id,hall_level,hall_name,rebet_config,rebet_condition')
                ->whereNotIn('hall_level',[4,6])
                ->where('lottery_id', $firstLotteryId)
                ->get()
                ->toArray();
        }

        return $lottery;
    }

    public function detail($id = null) {
        $lotteryConfig = new \Logic\Set\SystemConfig($this->ci);
        if ($id != 0) {
            $lottery = DB::table('lottery')
                ->selectRaw('id, name, pid, alias')
                ->where('id', $id)
                ->first();

            if (!$lottery) {
                return $this->lang->set(10015);
            }
        } else {
            $lottery = new stdClass();
            $lottery->id = 0;
            $lottery->pid = 0;
            $lottery->name = '全部';
            $lottery->alias = 'ALL';
        }

        $lottery->lottery_type = $lotteryConfig->lotteryConfig()['alias'];

        $query = DB::table('hall')
            ->selectRaw('GROUP_CONCAT(DISTINCT(hall_level)) AS hall_level, hall_name')
            ->groupBy('hall_level')
            ->where('hall_level', '<>', 6)
            ->where('hall_level', '<>', 4);

        if ($id != 0) {
            $lottery_ids = [$id];

            $result = DB::table('lottery')
                ->selectRaw('id')
                ->where('pid', $id)
                ->get()
                ->toArray();

            if ($result) {
                $lottery_ids = array_merge($lottery_ids, array_map(function ($row) {
                    return $row->id;
                }, $result));
            }

            $query->whereIn('lottery_id', $lottery_ids);
        }

        $lottery->halls = $query->get()
            ->toArray();

        $lottery->halls = array_map(function ($hall) {
            $hall->hall_name = '[' . $hall->hall_name . ']投注最小限额(元)';

            return $hall;
        }, $lottery->halls);

        $hall1 = new stdClass();
        $hall1->hall_level = -1;
        $hall1->hall_name = '所有单期投注最大限额(元)';

        $hall2 = new stdClass();
        $hall2->hall_level = -2;
        $hall2->hall_name = '个人单期投注最大限额(元)';

        $lottery->halls = array_merge([
            $hall1, $hall2,
        ], $lottery->halls);


        return [$lottery];
    }
};
