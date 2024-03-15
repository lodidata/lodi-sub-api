<?php
/**
 * 修改会员层级
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Log;
use Logic\Level\Level as levelLogic;
use Logic\Admin\BaseController;
use Logic\Level\Level as level;
use Model\Admin\UserLevel;
return new class() extends BaseController
{
    const TITLE = '修改等级';
    const DESCRIPTION = '修改等级';
    const QUERY = [
        'name' => 'string #等级名称',
        'deposit_money' => 'int #最低充值金额',
        'online_dml_percent' => 'float #线上充值打码量',
        'offline_dml_percent' => 'float #线下充值打码量',
        'level' => 'int #数字等级',
        'icon' => 'string #等级图标URL地址',
        'lottery_money' => 'int #最低投注量',
        'user_count' => 'int #该层级对应的会员人数',
        'upgrade_dml_percent' => 'float #提现打码量',
        'draw_count' => 'int #活动免费抽奖次数',
        'promote_handsel' => 'int #晋升彩金',
        'transfer_handsel' => 'int #转卡彩金',
        'monthly_money' => 'int #月俸禄彩金，分为单位',
        'monthly_percent' => 'int #月俸禄条件百分比',
        'online'=> 'string #层级对应的线上支付列表',
        'offline'=> 'string #层级对应的线下支付列表',
    ];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        if (!DB::table('user_level')->find($id)) {//检查层级是否存在
            return $this->lang->set(10015);
        }
//        (new LevelValidate())->paramsCheck('put', $this->request, $this->response);

        $params = $this->request->getParams();
        //判断是否是系统默认会员等级
        if($params['level'] == 1){
            $params['deposit_money'] = 0;
            $params['lottery_money'] = 0;
        }else{//检查上一个层级和下一个层级的最低充值金额和投注金额
            $before_value = DB::table('user_level')->where('level',$params['level']-1)->select('id','lottery_money','deposit_money')->get()->toArray();
            $after_value = DB::table('user_level')->where('level',$params['level']+1)->select('id','lottery_money','deposit_money')->get()->toArray();
            if(!empty($before_value)){
                $levels = $params['level'] - 1;
                if($params['lottery_money'] ==0 && $before_value[0]->lottery_money !=0){
                    return $this->lang->set(10900);
                }elseif($params['deposit_money'] ==0 && $before_value[0]->deposit_money !=0){
                    return $this->lang->set(10899);
                }else {
                    if ($params['lottery_money'] == 0 || $params['deposit_money'] == 0) {
                        if (($params['lottery_money'] <= $before_value[0]->lottery_money && $params['deposit_money'] <= $before_value[0]->deposit_money) && ($params['lottery_money'] <= $before_value[0]->lottery_money || $params['deposit_money'] <= $before_value[0]->deposit_money)) {
                            return $this->lang->set(10902, [$levels]);
                        }
                    } else {
                        if ($params['lottery_money'] <= $before_value[0]->lottery_money || $params['deposit_money'] <= $before_value[0]->deposit_money) {
                            return $this->lang->set(10902, [$levels]);
                        }
                    }
                }
            }
            if(!empty($after_value)){
                $levels = $params['level']+1;
                 if($params['lottery_money'] >0 && $after_value[0]->lottery_money ==0){
                        return $this->lang->set(10898,[$levels]);
                 }elseif($params['deposit_money'] >0 && $after_value[0]->deposit_money ==0){
                        return $this->lang->set(10898,[$levels]);
                 }else{
                     if($params['lottery_money'] == 0 || $params['deposit_money'] == 0){
                         if(($params['lottery_money'] >= $after_value[0]->lottery_money && $params['deposit_money'] >= $after_value[0]->deposit_money) && ($params['lottery_money'] >= $after_value[0]->lottery_money || $params['deposit_money'] >= $after_value[0]->deposit_money)){
                             return $this->lang->set(10903,[$levels]);
                         }
                     }else{
                         if($params['lottery_money'] >= $after_value[0]->lottery_money || $params['deposit_money'] >= $after_value[0]->deposit_money){
                             return $this->lang->set(10903,[$levels]);
                         }
                     }
                 }
            }
        }
        /*======日志操作代码=====*/
        $str = "";
        $data = DB::table('user_level')->find($id);
        $data = (array)$data;

        $name_arr = [
            'offline_dml_percent' => '线下充值打码量%(0-100)',
            'online_dml_percent' => '线上充值打码量%(0-100)',
            'memo' => '备注',
            'name' => '名称',
            'deposit_min' => '最低充值金额',
            'deposit_times' => '最低充值次数',
            'use_time_min' => '最低使用时长（月）',
            'monthly_money' => '月俸禄金额',
            'monthly_percent' => '月俸禄百分比',
            'monthly_recharge' => '月俸禄充值条件',
            'bankcard_sum' => '银行卡绑定数',
            'welfare'      => '福利特权',
            'week_money'   => '周薪',
            'fee'          => '提现手续费',
        ];
        if ($params['welfare']['withdraw_max'] > $params['welfare']['daily_withdraw_max'] && $params['welfare']['daily_withdraw_max'] != 0)  return $this->lang->set(11067);
        if ($params['welfare']) $params['welfare'] = json_encode($params['welfare']);
        $levelLogic = new Level($this->ci);
        $online = $levelLogic->getLevelOnlineSet($id);
        $old_offline = $this->getLevelOfflieSet($id);

        $old_online_str=implode(',',$online);
        $old_offline_str=implode(',',$old_offline);

        /*============================================================*/
        $res = $this->updateLevel($id, $params);
        $new_offline = $this->getLevelOfflieSet($id);
        $new_offline_str=implode(',',$new_offline);
        $new_online=implode(',',$params['online']);

        foreach ($data as $key => $datum) {
            foreach ($name_arr as $key2 => $item) {
                if ($key2 == $key) {
                    if ($datum != $params[$key2]) {
                        if ($key == 'offline_dml_percent') {
                            $str .= "/{$name_arr[$key]}：[" . ($datum * 100) . "%]更改为[" . ($params[$key] * 100) . "%]";
                        } else if ($key == 'online_dml_percent') {
                            $str .= "/{$name_arr[$key]}：[" . ($datum * 100) . "%]更改为[" . ($params[$key] * 100) . "%]";
                        } else if ($key == 'deposit_min') {
                            $str .= "/{$name_arr[$key]}：[" . ($datum / 100) . "]更改为[" . ($params[$key] / 100) . "]";
                        } else {
                            $str .= "/{$name_arr[$key]}：[{$datum}]更改为[{$params[$key]}]";
                        }
                    }
                }
            }
        }

        if($new_offline_str!=$old_offline_str){
            $str=$str."/线下充值渠道:[$old_offline_str]更改为[$new_offline_str]";
        }
        if($new_online!=$old_online_str){
            $str=$str."/线上充值渠道:[$old_offline_str]更改为[$new_online]";
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员等级', '管理等级', '设置', $sta, "等级名称：{$params['name']}/$str");
        /*============================================================*/
        return $res;

    }

    /**
     * 更新层级
     * @param $levelId 层级id
     * @param $params 层级参数
     */
    protected function updateLevel($levelId, $params)
    {
        $level = new UserLevel();
        $level = $level::find($levelId);
        $level->deposit_money = $params['deposit_money'];
        $level->offline_dml_percent = $params['offline_dml_percent'];
        $level->online_dml_percent = $params['online_dml_percent'];
        $level->icon = replaceImageUrl($params['icon']);
        $level->background       = replaceImageUrl($params['background']);
        $level->level_background = replaceImageUrl($params['level_background']);
        $level->lottery_money = $params['lottery_money'];
        $level->user_count = $params['user_count'];
        $level->upgrade_dml_percent = $params['upgrade_dml_percent'];
        $level->promote_handsel = $params['promote_handsel'];
        $level->transfer_handsel = $params['transfer_handsel'];
        $level->draw_count = $params['draw_count'];//活动免费抽奖次数
        $level->monthly_money = $params['monthly_money'];//月俸禄金额，以分为单位
        $level->monthly_percent = $params['monthly_percent'];//月俸禄达到晋升投注量的百分比，需要乘以100
        $level->monthly_recharge = isset($params['monthly_recharge'])?$params['monthly_recharge']:0;
        $level->updated = (string)$level->updated;
        $level->bankcard_sum = (int)$params['bankcard_sum'] ?? 1;
        $level->week_money      = $params['week_money'];
        $level->welfare         = $params['welfare'];
        $level->fee             = $params['fee'];
        $level->week_recharge   = $params['week_recharge'];
        $level->split_line = replaceImageUrl($params['split_line']);
        $level->font_color = $params['font_color'];
        //设置周薪发放时间
        $this->redis->set(\Logic\Define\CacheKey::$perfix['week_award_day'], $params['week_award_day']);//设置回水时间
        $this->redis->set(\Logic\Define\CacheKey::$perfix['week_award_time'], $params['week_award_time']);//设
        DB::beginTransaction();
        try {
            $level->save();
//            if (isset($params['online']) && count($params['online'])){
//                $levelLogic = new levelLogic($this->ci);
//                if (is_array($params['online']) && count($params['online'])) {
//                    $onlineData = [];
//                    foreach ($params['online'] ?? [] as $k => $v) {
//                        $onlineData[] = ['level_id' => $levelId, 'pay_plat' => $v];
//                    }
//                    $levelLogic->onlineSet($levelId, $onlineData);
//                } else {
//                    //必须先删除之前线上支付的数据
//                    DB::table('level_online')->where('level_id', $levelId)->delete();
//                }
//                if (is_array($params['offline']) && count($params['offline'])) {
//                    $offlineData = [];
//                    foreach ($params['offline'] ?? [] as $k => $v) {
//                        $offlineData[] = ['level_id' => $levelId, 'pay_id' => $v];
//                    }
//                    $levelLogic->offlineSet($levelId, $offlineData);
//                } else {
//                    //必须先删除之前线下支付的数据
//                    DB::table('level_offline')->where('level_id', $levelId)->delete();
//                }
//            }
            DB::commit();
            return $this->lang->set(0);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->lang->set(10404);
        }
        return $this->lang->set(0);
    }

    /**
     * 获取层级线下支付列表，需连表查出名称，数据库只存了ID
     */
    protected function getLevelOfflieSet($levelId)
    {
        $data = DB::table('level_offline as l')
            ->where('l.level_id', $levelId)
            ->leftJoin('bank_account as b', 'l.pay_id', '=', 'b.id')
            ->whereRaw("find_in_set('enabled',state)")
            ->select(['b.name', 'b.type'])
            ->get()->toArray();
        $result = [];
        $typeArr = ['1' => '网银', '2' => '支付宝', '3' => '微信', '4' => 'QQ支付', '5' => '京东支付'];
        foreach ($data ?? [] as $k => $v) {
            $result[] = $typeArr[$v->type] . "-" . $v->name;
        }
        return $result;
    }

};
