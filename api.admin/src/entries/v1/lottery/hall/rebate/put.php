<?php
/**
 * 会员等级-回水设置
 * @author Taylor
 */
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '会员等级-回水设置';
    //彩票 {"menu":"CP","rebot_time":"5","rebot_way":{"type":"loss","data":{"status":"percentage","value":["1000,5000;10;0","5000,50000;12;0","50000,500000;15;0","500000,100000000;18;0"]}},"user_level_id":22,"status_switch":1,"hall_level":1,"lottery_type":"pc28","rebet_condition":[{"type":"group_gt","value":"20","checked":true},{"type":"blend_lt","value":"30","checked":true},{"type":"guess_gt","value":"1","checked":true},{"type":"group_lt","value":"10","checked":true},{"type":"betting_gt","value":"15","checked":true},{"type":"betting_all_gt","value":"4","checked":true}],"rebet_ceiling":100000}
    //{"menu":"CP","rebot_time":"5","rebot_way":{"type":"betting","data":{"status":"percentage","value":["100,100000000;0.2;0"]}},"user_level_id":22,"status_switch":1,"hall_level":1,"lottery_type":"k3","rebet_condition":[{"type":"betting_gt","value":"1","checked":true}],"rebet_ceiling":100000}
    //棋牌 {"menu":"QP","rebot_time":"5","rebot_way":{"type":"betting","data":{"status":"percentage","value":["1,200;1;1"]}},"user_level_id":22,"status_switch":0,"game_id":4,"rebet_ceiling":100}
    const PARAMS = [
        "menu" => "string(require) #一级菜单 CP为彩票，BY为捕鱼，QP为棋牌，SPORT为体育，LIVE为真人，GAME为电子",
        "rebot_time" => "int(require) #回水时间，5表示每日的5点",
        "rebot_way" => "enum[betting,loss](require) #回水方式 type:betting按照当日投注额回水；type:loss按照当日亏损额回水",
        "rebet_condition" => "enum(require) #回水条件 type:group_gt组合；type:blend_lt混合（大双、小单）投注额；type:guess_gt猜和值；type:group_lt混合（大、小、单、双）的投注额；type:betting_gt当天投注期数不小于x；type:betting_all_gt当天总投注额",
        "rebet_ceiling" => "int(require) 回水上限",
        "user_level_id" => "int(require) 层级id",
        "lottery_type" => "string(require) 彩票类型：pc28 幸运28类；k3 快三类；ssc 时时彩；11x5 11选5类；sc 赛车类；lhc 六合彩类",
        "hall_level" => "int(require) 模式类型：1 回水厅类；2 保本厅类；3 高赔率厅类；4 PC房；5 传统模式类",
        "status_switch" => "int(require) 回水开关，1表示启动，0表示关闭",
        "game_id" => "array(require) game_menu里的id 多个就组成数组，例如[1,2,3]",
    ];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $levelArray = [
            '1' => '回水厅',
            '2' => '保本厅',
            '3' => '高赔率厅',
            '4' => 'PC房',
            '5' => '传统',
            '6' => '直播',
        ];
        if(!isset($params['status_switch'])){
            $params['status_switch'] = 0;
        }
        if(isset($params['rebet_multiple'])) {
            $validate = new \lib\validate\BaseValidate([
                'rebet_multiple' => 'integer|egt:0|elt:99',
            ], [], [
                "rebet_multiple" => "日返流水倍数",
            ]);
            $validate->paramsCheck('', $this->request, $this->response);
        }
        if(!isset($params['rebet_multiple'])){
            $params['rebet_multiple'] = 0;
        }
        if(!isset($params['rebet_gt_zero_switch'])){
            $params['rebet_gt_zero_switch'] = false;
        }
        $level = DB::table('user_level')->where('id', $params['user_level_id'])->get()->first();
        if(empty($level)){
            return $this->lang->set(886, ['层级不存在']);
        }
        $game_id_list = is_numeric($params['game_id']) ? (array)$params['game_id'] : $params['game_id'];
        foreach ($game_id_list as $v){
            $params['game_id'] = $v;
            $res = $this->updateRebetConfig($params, $levelArray);
        }
        if (isset($params['rebet_multiple']) || isset($params['rebet_gt_zero_switch'])) {
            $data['rebet_config'] = $tmp['rebet_config'] = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
            $data['rebet_config']['day'][$params['user_level_id']] = intval($params['rebet_multiple']);
            $data['rebet_config']['day_gt_zero'][$params['user_level_id']] = boolval($params['rebet_gt_zero_switch']);
            $confg = new \Logic\Set\SystemConfig($this->ci);
            $confg->updateSystemConfig($data, $tmp);
        }
        /*====== end =====*/
        if ($res === false) return $this->lang->set(-2);
        return $this->lang->set(0);
    }

    public function updateRebetConfig($params, $levelArray){
        if ($params['menu'] != 'CP') {//一级菜单非彩票类的第三方大厅配置信息表
            $r_time = $this->redis->set(\Logic\Define\CacheKey::$perfix['rebot_time'], $params['rebot_time']);//设置回水时间
            $r_time_minute=$this->redis->set(\Logic\Define\CacheKey::$perfix['rebot_time_minute'], $params['rebot_time_minute']);//设置回水时间
            $data_rebet = DB::table('hall_3th')->where('game_id', $params['game_id'])->get()->first();
            if (empty($data_rebet)) {//判断第三方大厅设置是否存在
                $values = DB::table('game_menu')->selectRaw('id,name')->where('id', $params['game_id'])->get()->toArray();
                foreach ($values as $vl) {
                    $itDate = [
                        'game_id'         => $vl->id,//游戏id
                        'rebet_condition' => isset($params['rebet_condition']) ? json_encode($params['rebet_condition']) : null,
                        'rebot_way'       => json_encode($params['rebot_way']),
                        'game_name'       => $vl->name,//游戏名称
                        '3th_name'        => $params['menu'],//第三方游戏名所属分类
                        'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,
                    ];
                    $res = DB::table('hall_3th')->insert($itDate);
                }
            }
            if (isset($params['user_level_id'])) {//用户层级id
                $rebet_val = DB::table('rebet_config')->select('id')
                    ->where('user_level_id', $params['user_level_id'])
                    ->where('game3th_id', $params['game_id'])->get()->toArray();
                if (empty($rebet_val)) {
                    $rebetDate = [
                        'user_level_id'   => $params['user_level_id'],//层级id
                        'game3th_id'      => $params['game_id'],//第三方游戏id
                        'rebet_condition' => isset($params['rebet_condition']) ? json_encode($params['rebet_condition']) : null,//回水条件
                        'rebot_way'       => json_encode($params['rebot_way']),//回水方式
                        'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,//回水上限
                        'status_switch'   => $params['status_switch'],//回水开关
                    ];
                    DB::table('rebet_config')->insert($rebetDate);
                    $res = 0;
                } else {
                    $rebetDate = [
                        'rebet_condition' => isset($params['rebet_condition']) ? json_encode($params['rebet_condition']) : null,
                        'rebot_way'       => json_encode($params['rebot_way']),
                        'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,
                        'status_switch'   => $params['status_switch'],
                    ];
                    $res = DB::table('rebet_config')->where('game3th_id', $params['game_id'])
                        ->where('user_level_id', $params['user_level_id'])->update($rebetDate);
                }
            }
            if ($res == 0) {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '厅设置', '回水设置', '新增', 1, '无数据新增');
            } else {
                $sta = $res !== false ? 1 : 0;
                (new Log($this->ci))->create(
                    null, null, Log::MODULE_LOTTERY, '厅设置', '回水设置', '编辑', $sta, '更新live成功' . '回水时间' . $r_time
                );
            }
        } else {//彩票类回水设置
            //return $this->lang->set(886,['彩票功能禁用']);
            foreach ($params['rebet_condition'] as $kr => $vr) {//回水条件设置
                //type表示回水条件对应的类型，value表示类型值，checked表示是否选中
                if (!isset($vr['type']) || !isset($vr['value']) || !isset($vr['checked'])) {
                    return $this->lang->set(10904);
                }
            }
            $validate = new BaseValidate([
                'lottery_type'    => 'require|in:pc28,k3,ssc,11x5,sc,lhc,bjc',
                'rebet_condition' => 'require',
                'hall_level'       => 'require|isPositiveInteger|between:1,6',
            ]);
            $validate->paramsCheck('', $this->request, $this->response);
            $r_time = $this->redis->set(\Logic\Define\CacheKey::$perfix['rebot_time'], $params['rebot_time']);//设置回水时间
            $r_time_minute = $this->redis->set(\Logic\Define\CacheKey::$perfix['rebot_time_minute'], $params['rebot_time_minute']);//设置回水时间
            //获取修改前的数据
            $data_rebet = DB::table('hall')->where('type', $params['lottery_type'])
                ->where('hall_level', $params['hall_level'])->get()->first();
            if (empty($data_rebet)) {
                $upData_s = ['type' => $params['lottery_type']];//更新彩种类型
                $rv = DB::table('hall')->where('type', $params['lottery_type'])
                    ->groupBy('lottery_id')->get()->toArray();
                foreach ($rv as $kv => $vls) {
                    $rvs = DB::table('hall')->where('lottery_id', $vls->lottery_id)->update($upData_s);
                }
                if (!$rvs) {
                    return $this->lang->set(10414);
                }
            }
            $data_rebet = (array)$data_rebet;

            $lotteryName = DB::table('lottery')->where('id', '=', $data_rebet['lottery_id'])->value('name');
            $str = "彩种类型:{$lotteryName}/厅类型:{$levelArray[$params['hall_level']]}/\n回水条件: \n";
            /*==== end =====*/
            //更新厅的彩票类型和模式类型条件下的回水条件和回水方式
            $upData = [
                'rebet_condition' => json_encode($params['rebet_condition']),//回水条件
                'rebot_way'       => json_encode($params['rebot_way']),//回水方式
                'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,//回水上限
            ];
            $res = DB::table('hall')->where('type', $params['lottery_type'])->where('hall_level', $params['hall_level'])->update($upData);
            if (isset($params['user_level_id'])) {
                $params['hall_level'] = $params['hall_level'] == 5 ? [4,5] : [$params['hall_level']];
                $hall_value = DB::table('hall')->select('id')->where('type', $params['lottery_type'])
                    ->whereIn('hall_level', $params['hall_level'])->get()->toArray();//彩票类型对应
                foreach ($hall_value as $hall_id) {
                    $rebet_val = DB::table('rebet_config')->select('id')
                        ->where('user_level_id', $params['user_level_id'])
                        ->where('hall_id', $hall_id->id)
                        ->get()->toArray();//回水设置
                    if (empty($rebet_val)) {
                        $rebetDate = [
                            'user_level_id'   => $params['user_level_id'],//层级id
                            'hall_id'         =>  $hall_id->id,//厅id
                            'rebet_condition' => isset($params['rebet_condition']) ? json_encode($params['rebet_condition']) : null,
                            'rebot_way'       =>  json_encode($params['rebot_way']),
                            'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,
                            'status_switch'   => $params['status_switch'],
                        ];
                        DB::table('rebet_config')->insert($rebetDate);//插入厅回水设置表
                        $res = 0;
                    } else {
                        $rebetDate = [
                            'rebet_condition' => isset($params['rebet_condition']) ? json_encode($params['rebet_condition']) : null,
                            'rebot_way'       => json_encode($params['rebot_way']),
                            'rebet_ceiling'   => isset($params['rebet_ceiling']) ? $params['rebet_ceiling'] : null,
                            'status_switch'   => $params['status_switch'],
                        ];
                        $res = DB::table('rebet_config')->where('user_level_id', $params['user_level_id'])
                            ->where('hall_id', $hall_id->id)->update($rebetDate);//更新厅回水设置表
                    }
                }
            }
            /*===操作日志代码== start ====*/
            if ($res == 0) {//新增
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '厅设置', '回水设置', '新增', 1, '无数据新增');
            } else {
                $sta = $res !== false ? 1 : 0;
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '厅设置', '回水设置', '编辑', $sta, '->回水时间' . $r_time . $r_time_minute, $str);
            }
        }
        return $res;
    }
};
