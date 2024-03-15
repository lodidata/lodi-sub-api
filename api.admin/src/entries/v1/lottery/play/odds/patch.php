<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '玩法数据设定';

    const DESCRIPTION = '';

    

    const PARAMS = [
        'id'           => 'string(required) # 赔率ID 传*时表示批量将这个玩法的全部修改',
        'reward_radio' => 'int() # 返奖率',
        'odds'         => 'int() # 赔率',
        'play_id'      => 'int(required) # 玩法ID',
        'hall_id'      => 'int(required) # 厅ID',
        'lottery_id'   => 'int(required) # 彩种ID',
    ];

    

    public $id;

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        (new BaseValidate([
            'id'           => 'require|checkValueByRegex:oddsid',
            'play_id'      => 'require|isPositiveInteger',
            'hall_id'      => 'require|isPositiveInteger',
            'lottery_id'   => 'require|isPositiveInteger',
            'reward_radio' => 'requireIf:id,*|>:0',
        ], [], [
                'reward_radio' => '赔率',
            ]
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();

        $params['id'] = $params['id'] == '*' ? $params['id'] : intval($params['id']);
        $params['play_id'] = intval($params['play_id']);
        $params['hall_id'] = intval($params['hall_id']);
        $params['lottery_id'] = intval($params['lottery_id']);

        if ($params['id'] == '*') {
            if (empty($params['reward_radio'])) {
                return $this->lang->set(10418);
            }

            $reward_radio = $params['reward_radio'] / 100;

            $query = DB::table('lottery_play_odds')
                       ->where('play_id', $params['play_id'])
                       ->where('hall_id', $params['hall_id'])
                       ->where('lottery_id', $params['lottery_id'])
                       ->update(
                           [
                               'odds' => DB::raw("$reward_radio * max_odds"),
                           ]
                       );
        } else {
            if (isset($params['odds']) && (empty($params['odds']) || floatval($params['odds']) == 0 || floor($params['odds'] * 100) == 0)) {
                return $this->lang->set(10418);
            }
            //
            $lott_info = DB::table('hall')
                ->select('lottery.name as lott_name', 'hall_name','hall_level')
                ->leftJoin('lottery', 'lottery.id', '=', 'lottery_id')
                ->where('hall.id', '=', $params['hall_id'])
                ->get()
                ->first();
            $info = DB::table('lottery_play_odds')
                ->select('odds', 'play_id', 'name','play_sub_id')
                ->where('id', $params['id'])
                ->get()
                ->first();
            $info = (array)$info;
            $lott_info = (array)$lott_info;
            //传统与PC 合二为一，更新传统PC 赔率相应更新  5传统  4PC
            $ids[] = $params['id'];
            if($lott_info['hall_level'] == 5 ) {
                $t = DB::table('hall')
                    ->where('lottery_id', '=', $params['lottery_id'])
                    ->where('hall_level', '=', 4)
                    ->value('id');
                $ids[] = DB::table('lottery_play_odds')
                    ->where('hall_id', '=', $t)
                    ->where('lottery_id', '=', $params['lottery_id'])
                    ->where('play_id', '=', $params['play_id'])
                    ->where('play_sub_id', '=', $info['play_sub_id'])
                    ->value('id');
            }
            if (!empty($params['odds'])) {
                /*
                $findresult = DB::table('lottery_play_odds')
                                ->where('id', $params['id'])
                                ->first();

                if ($findresult->max_odds < $params['odds']) {
                    return $this->lang->set(10410);
                }
                */
                /* ==================================日志操作代码   获取数据==============================*/

                $play_group_name = DB::table('lottery_play_struct')
                                     ->select('group', 'play_text1', 'name')
                                     ->where('play_id', '=', $info['play_id'])
                                     ->get()
                                     ->first();

                $play_group_name = (array)$play_group_name;

                /* =============================================================================*/

                $query = DB::table('lottery_play_odds')
                           ->whereIn('id', $ids)
                           ->update([
                               'odds' => $params['odds'],
                           ]);
                /* ==================================日志操作代码===========================================*/

                $sta = 0;
                if ($query !== false) {
                    $sta = 1;
                }

                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '赔率设置', '当前赔率', '编辑', $sta, "彩种名称:{$lott_info['lott_name']}/模式类型:{$lott_info['hall_name']}/玩法列表:{$play_group_name['name']}/玩法细则:{$info['name']}/赔率：[{" . ($info['odds']) . "}]更改为[{$params['odds']}]");
                /* =============================================================================*/

            } else {
                $reward_radio = floatval($params['reward_radio']) / 100;

                $query = DB::table('lottery_play_odds')
                           ->whereIn('id', $ids)
                           ->update([
                               'odds' => DB::raw("$reward_radio * max_odds"),
                           ]);
            }
        }

        if ($query !== false) {
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);

    }

    protected function _removeCache($lotteryId, $hallId) {
        $redis = $this->redis;
        $redis->del('lottery_play_struct_' . $lotteryId . '_' . $hallId);
        $redis->incr('lottery_play_struct_ver_' . $lotteryId . '_' . $hallId);
    }


};