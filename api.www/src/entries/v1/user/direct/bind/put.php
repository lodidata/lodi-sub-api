<?php

use Utils\Www\Action;
use Logic\Admin\Log;

return new class() extends Action {
    const TITLE = '直推绑定上级代理';
    const DESCRIPTION = '直推绑定上级代理';
    const QUERY = [

    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        if (empty($user_id)) {
            return $this->lang->set(11, [], [], []);
        }

        $params = $this->request->getParams();
        $pr_code = $params['pr_code'] ?? '';            //推广码
        //检测推广码是否为空
        if (empty($pr_code)) {
            return $this->lang->set(10, [], [], []);
        }

        //根据二维码检测要绑定的上级信息是否存在
        $ck_sup_agent = (array)DB::connection('slave')->table('user_agent')->where('code', $pr_code)->first();
        if (!$ck_sup_agent) {
            $get_agent_id = DB::connection('slave')->table('agent_code')->where('code',$pr_code)->value('agent_id');
            $ck_sup_agent = (array)DB::connection('slave')->table('user_agent')->where('user_id', $get_agent_id)->first();
        }
        if (empty($ck_sup_agent)) {
            return $this->lang->set(10015);
        }
        $ck_sup_userInfo = (array)DB::connection('slave')->table('user')->selectRaw('id,name,wallet_id,agent_switch')->where('id',$ck_sup_agent['user_id'])->first();
        if (empty($ck_sup_userInfo)) {
            return $this->lang->set(10015);
        }

        //检测当前用户是否已经存在上级代理
        $sub_agent = (array)DB::connection('slave')->table('user_agent')->whereRaw('user_id=?',[$user_id])->first();
        if (isset($sub_agent['uid_agent']) && ($sub_agent['uid_agent']>0)) {
            return createRsponse($this->response, 200, -2, "已经存在上级代理无法绑定", [], []);
        }

        //检测要绑定的上级是否为自己
        if($ck_sup_agent['user_id'] == $user_id){
            return $this->lang->set(10557);
        }
        //当前用户旧的所有上级
        $oldBeforeIds = explode(',',$sub_agent['uid_agents']);  //原上级  最后包括自己
        //保存规则是最后是自己  如  ： 一级,二级,三级,自己
        if($user_id != array_pop($oldBeforeIds)) {
            return $this->lang->set(10557);
        }
        //查新代理是否为曾经的下级
        $agent=new \Logic\User\Agent($this->ci);
        $currSubAgentIds = $agent->getAllSubAgentIds($user_id);
        if($ck_sup_agent['user_id'] > 0){
            if(in_array($ck_sup_agent['user_id'],$currSubAgentIds)){  //不能移动到自己的下级
                return $this->lang->set(10557);
            }
        }
        //新上级
        $agentIds = explode(',', $ck_sup_agent['uid_agents']); //移动后所有的上级
        $newUidAgents = $ck_sup_agent['uid_agents'] . ',' . $user_id;
        $new_level_count = count($agentIds);
        $old_level_count = count($oldBeforeIds);
        //计算等级差，旧3级变新2级表示升1级，旧1级变新6级下表示降为第7级
        $levelDiff = $old_level_count>=$new_level_count ?  ($new_level_count - $old_level_count) : ($new_level_count);
        try {
            \DB::beginTransaction();
            //邀请成功开启代理
            if ($ck_sup_userInfo['agent_switch'] == 0){
                \DB::table('user')->where('id', $ck_sup_userInfo['id'])->update(['agent_switch'=>1,'agent_time'=>date('Y-m-d H:i:s',time())]);
                $profit = $agent->getProfit($ck_sup_userInfo['id']);
                if($profit){
                    DB::table('user_agent')->where('user_id',$ck_sup_userInfo['id'])->update(['profit_loss_value'=>$profit]);
                }
                (new Log($this->ci))->create($ck_sup_userInfo['id'], $ck_sup_userInfo['name'], Log::MODULE_USER, '直推绑定', '直推/直推绑定', '绑定上级代理', 1, '推广下级user_id: '.$user_id.'，触发开启代理');
            }
            //删除自己与上级的关系
            if($oldBeforeIds){
                \DB::table('child_agent')->where('cid', $user_id)->delete();
                if ($currSubAgentIds) {   //所有下级有需要删掉与当前移到用户的上级的关系
                    DB::table('child_agent')->whereIn('pid', $oldBeforeIds)->whereIn('cid', $currSubAgentIds)->delete();
                }
            }
            //新上级关系
            if($ck_sup_agent['user_id'] > 0){
                //增加上级与下级的关系
                $arr_child_agents = [];
                foreach($agentIds as $v1){
                    $arr_child_agents[] = ['pid' => $v1,'cid'=>$user_id];
                    if ($currSubAgentIds) {
                        foreach ($currSubAgentIds as $v2) {
                            $arr_child_agents[] = ['pid' => $v1, 'cid' => $v2];
                        }
                    }
                }
                if(!empty($arr_child_agents)){
                    DB::table('child_agent')->insert($arr_child_agents);
                }
            }

            //更新旧上级的下级数
            if($oldBeforeIds) {
                DB::table('user_agent')->whereIn('user_id', $oldBeforeIds)->update([
                    'inferisors_all' => \DB::raw("inferisors_all - {$sub_agent['inferisors_all']}")
                ]);
            }

            //新上级的下级数
            if($ck_sup_agent['user_id'] > 0 && !empty($agentIds)){
                DB::table('user_agent')->whereIn('user_id', $agentIds)->update([
                    'inferisors_all' => \DB::raw("inferisors_all + {$sub_agent['inferisors_all']}")
                ]);
            }

            //更新下级等级及代理关系
            DB::table('user_agent')->whereIn('user_id', $currSubAgentIds)->update([
                'level' => \DB::raw("abs(level + {$levelDiff})"),
                'uid_agents' => \DB::raw("replace(uid_agents,'{$sub_agent['uid_agents']}','{$newUidAgents}')"),
            ]);

            //修改当前等级
            DB::table('user_agent')->where('user_id',$user_id)->update([
                'uid_agent' => $ck_sup_userInfo['id'],
                'uid_agent_name' => $ck_sup_userInfo['name'],
                'uid_agents' => $newUidAgents,
                'level' => \DB::raw("abs(level + {$levelDiff})"),
            ]);

            DB::commit();
            /*============================日志操作代码================================*/
            $uname = DB::table('user')
                ->where('id', '=', $user_id)
                ->value('name');
            (new Log($this->ci))->create($user_id, $uname, Log::MODULE_USER, '直推绑定', '直推/直推绑定', '绑定上级代理', 1, '绑定上级代理 ['. $ck_sup_userInfo['name'].']');
            /*============================================================*/
        }catch (Exception $e){
            print_r($e->getMessage());
            DB::rollback();
            return $this->lang->set(-2);
        }

        //直推发奖
        $sysConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
        if (!isset($sysConfig['cash_promotion_register']['send_amount']) || !isset($sysConfig['cash_be_pushed_register']['send_amount'])) {
            return createRsponse($this->response, 200, -2, "系统配置数据为空", [], []);
        }
        //检测直推开关
        $direct_switch = $sysConfig['direct_switch'];
        if ($direct_switch != 1) {
            return ['code'=>-2,'msg'=>'直推开关已关闭'];
        }
        $direct_dml = $sysConfig['send_dml'];   //打码量
        //绑定成功给自己发奖励
        $userInfo = (array)DB::connection('slave')->table('user')->selectRaw('wallet_id,name')->whereRaw('id=?',[$user_id])->first();
        if (!isset($userInfo['wallet_id'])) {
            return createRsponse($this->response, 200, -2, "用户信息为空", [], []);
        }
        $award_config = DB::table('direct_imgs')->selectRaw('reward')->whereRaw('type=?',['no_bind'])->where('status','enabled')->first();
        if (empty($award_config) || !isset($award_config->reward)) {
            return createRsponse($this->response, 200, -2, "奖励配置数据为空", [], []);
        }
        $award = $award_config->reward * 100;
        //添加奖励记录到详情表 direct_record
        $self_direct_dml = $award * ($direct_dml / 100);
        $inData = [
            'user_id' => $user_id,
            'username' => $userInfo['name'],
            'sup_uid' => $ck_sup_userInfo['id'],
            'sup_name' => $ck_sup_userInfo['name'],
            'type' => 3,   //1-注册，2-充值，3-绑定上级
            'price' => $award,
            'dml' => $self_direct_dml,
            'is_transfer' => 1,
            'date' => date("Y-m-d"),
            'created' => date("Y-m-d H:i:s"),
            'updated' => date("Y-m-d H:i:s")
        ];
        DB::table('direct_record')->insertGetId($inData);
        //给用户发放绑定奖励
        (new \Logic\Wallet\Wallet($this->ci))->crease($userInfo['wallet_id'], $award);
//        $res = DB::table('funds')->whereRaw('id=?',[$userInfo['wallet_id']])->increment('balance', $award);
//        if (!$res) {
//            return createRsponse($this->response, 200, -2, "直推绑定奖励发放失败", [], []);
//        }
        //更新自己直推已获奖励数
        DB::table('user_data')->whereRaw('user_id=?',[$user_id])->update(['direct_award'=>DB::raw('direct_award + '.$award)]);
        //添加交易流水记录
        $obj = new \Logic\Recharge\Recharge($this->ci);
        $obj->addFundsDealLog($user_id,$userInfo['name'],$userInfo['wallet_id'],$self_direct_dml,$award,"直推-绑定上级奖励");

        //绑定成功给上级发奖励
        $supData = [
            'user_id' => $ck_sup_userInfo['id'],
            'username' => $ck_sup_userInfo['name'],
            'sup_uid' => $ck_sup_userInfo['id'],
            'sup_name' => $ck_sup_userInfo['name'],
            'type' => 3,   //1-注册，2-充值，3-绑定上级
            'price' => $award,
            'dml' => $self_direct_dml,
            'is_transfer' => 1,
            'date' => date("Y-m-d"),
            'created' => date("Y-m-d H:i:s"),
            'updated' => date("Y-m-d H:i:s")
        ];
        DB::table('direct_record')->insertGetId($supData);
        //给用户发放绑定奖励
        (new \Logic\Wallet\Wallet($this->ci))->crease($ck_sup_userInfo['wallet_id'], $award);
//        $res = DB::table('funds')->whereRaw('id=?',[$ck_sup_userInfo['wallet_id']])->increment('balance', $award);
//        if (!$res) {
//            return createRsponse($this->response, 200, -2, "直推绑定奖励发放失败", [], []);
//        }
        //更新自己直推已获奖励数, 推广人数
        DB::table('user_data')->whereRaw('user_id=?',[$ck_sup_userInfo['id']])->update(['direct_award'=>DB::raw('direct_award + '.$award),'direct_register'=>DB::raw('direct_register + 1'),'direct_reg_award'=>DB::raw('direct_reg_award + '.$award)]);
        //添加交易流水记录
        $obj = new \Logic\Recharge\Recharge($this->ci);
        $obj->addFundsDealLog($ck_sup_userInfo['id'],$ck_sup_userInfo['name'],$ck_sup_userInfo['wallet_id'],$self_direct_dml,$award,"直推-绑定上级奖励");
        $rebetObj = new \Logic\Lottery\Rebet($this->ci);
        $rebetObj->updateUserDirectBkgeIncr($ck_sup_userInfo['id']); // 直推返水比例

        return $this->lang->set(0, [], [], []);
    }
};
