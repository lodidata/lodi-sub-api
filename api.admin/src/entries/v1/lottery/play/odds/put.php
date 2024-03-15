<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '更改赔率接口（新版）';
    const DESCRIPTION = '更改赔率接口';

    const PARAMS = [
        'id'           => 'string() # 赔率ID 传*时表示批量将这个玩法的全部修改',
        'reward_radio' => 'int() # 返奖率',
        'odds'         => 'int() # 赔率',
        'play_id'      => 'int() # 玩法ID',
        'hall_id'      => 'int() # 厅ID',
        'group'        => 'string() #玩法名',
        'lottery_id'   => 'int() # 彩种ID',
        'lottery_pid'  => 'int() # 彩种父ID',
    ];


    public $id;

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $valid = new BaseValidate([
            'id'           => 'isPositiveInteger',
            'play_id'      => 'isPositiveInteger',
            'hall_id'      => 'isPositiveInteger',
            'lottery_id'   => 'isPositiveInteger',
            'lottery_pid'  => 'isPositiveInteger',
            'reward_radio' => 'requireIf:id,*|number|>:0',
            'odds'         => 'requireWith:id',
        ], [], [
                'reward_radio' => '返奖率',
                'odds'         => '赔率',
            ]
        );

        $valid->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();
        if (empty($params['reward_radio']) && empty($params['odds'])) {
            return $this->lang->set(10418);
        }

        $query = DB::table('lottery_play_odds');

        //设置单个玩法的赔率
        if (isset($params['id']) && isset($params['odds'])) {
            $exists = DB::table('lottery_play_odds')
                ->find($params['id']);

            if (!$exists) {
                return $this->lang->set(10015);
            }

            $find = DB::table('lottery_play_odds')
                ->where('id', $params['id'])
                ->first();

            if ($find->max_odds < $params['odds']) {
                return $this->lang->set(10410);
            }

            $result = $query->where('id', $params['id'])
                ->update([
                    'odds' => $params['odds'],
                ]);

        } else if (isset($params['reward_radio'])) {
            $reward_radio = $params['reward_radio'] / 100;

            foreach (['play_id', 'hall_id', 'lottery_id', 'lottery_pid'] as $field) {
                if (!isset($params[$field])) {
                    continue;
                }

                $query->where($field, $params[$field]);
            }

            /*============================================日志操作代码================================================*/
            $str='';

            if(isset($params['lottery_pid'])){
                $lottery=DB::table('lottery')
                    ->select('name')
                    ->where('id','=',$params['lottery_pid'])
                    ->get()
                    ->first();
                $lottery_name=(array)$lottery;
                $str.="彩种类型:{$lottery_name['name']}/";
            }


            if(isset($params['hall_id'])){
                $hall=DB::table('hall')
                    ->select('hall_name')
                    ->where('id','=',$params['hall_id'])
                    ->get()
                    ->first();
                $hall_name=(array)$hall;
                $str.="模式类型:{$hall_name['hall_name']}/";
            }

            if(isset($params['lottery_id'])){
                $lottery=DB::table('lottery')
                    ->select('name')
                    ->where('id','=',$params['lottery_id'])
                    ->get()
                    ->first();
                $lottery_name=(array)$lottery;
                $str.="彩种名称:{$lottery_name['name']}/";
            }

            if(isset($params['play_id'])){
                $play_id=DB::table('lottery_play_odds')
                    ->select('play_id')
                    ->where('id','=',$params['play_id'])
                    ->get()
                    ->first();
                $play_id=(array)$play_id;
                $play_group_name=DB::table('lottery_play_struct')
                    ->select('group','play_text1')
                    ->where('play_id','=',$play_id['play_id'])
                    ->get()
                    ->first();
                $play_group_name=(array)$play_group_name;
                $str.="玩法列表:{$play_group_name['group']}/玩法细则{$play_group_name['play_text1']}";
            }
            /*============================================日志操作代码================================================*/

            if (isset($params['group']) && isset($params['lottery_pid'])) {
                $plays = DB::table('lottery_play_struct')
                    ->selectRaw('`play_id`')
                    ->where('lottery_pid', $params['lottery_pid'])
                    ->where('group', $params['group'])
                    ->get()
                    ->toArray();

                if ($plays) {
                    $play_ids = array_map(function ($play) {
                        return $play->play_id;
                    }, $plays);

                    $query->whereIn('play_id', $play_ids);
                }
            }

            $builder = clone $query;

            $max_odds = $builder->get(['max_odds'])->toArray();

            foreach ($max_odds as $odds) {
                $odd = $odds->max_odds;

                if (round($odd * $reward_radio, 2) == 0) {
                    return $this->lang->set(10419);
                }
            }

            $result = $query->update([
                'odds' => DB::raw("$reward_radio * max_odds"),
            ]);

            if(!isset($params['lottery_pid'])&&!isset($params['group']) &&!isset($params['play_id'])&&!isset($params['lottery_id'])&&!isset($params['hall_id'])){
                $str="彩种类型：全部/彩种名称：全部/模式类型：全部/玩法列表：全部/彩种类型：全部/玩法：全部";
            }

            $str=$str."/返奖率[{$params['reward_radio']}%]";
        } else {
            return $this->lang->set(10013);
        }

        if ($result === false) {
            return $this->lang->set(-2);
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '赔率设置', '赔率设置', '返奖率批量设置', 1, $str);
        return $this->lang->set(0);
    }

    protected function _removeCache($lotteryId, $hallId) {
        $redis = $this->redis;
        $redis->del('lottery_play_struct_' . $lotteryId . '_' . $hallId);
        $redis->incr('lottery_play_struct_ver_' . $lotteryId . '_' . $hallId);
    }
};