<?php

use Logic\Admin\Log;
use Logic\Level\Level as Level;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const TITLE = '更换上级代理';
    //无上级
    private $super_agent_id = 999999999;
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($user_id = '')
    {

        $this->checkID($user_id);

        (new BaseValidate(
            [
            'agent_id'=>'require|isPositiveInteger',
            'agent_name'=>'require',
            ],
            [],
            ['agent_id'=>'代理id',]

        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
        if($user_id == $params['agent_id']){
            return $this->lang->set(10558);
        }


        //当前代理信息
        $user = (array)\DB::table('user_agent')->where('user_id',$user_id)->first(['id','user_id','uid_agent','uid_agent_name','uid_agents','level','inferisors_all']);
        //新代理信息
        $new_agent_id = $params['agent_id'];
        $new_agent_name = $params['agent_name'];
        //新代理为无上线（顶级）
        if($new_agent_id == $this->super_agent_id){
            $new_agent_id = 0;
            $new_agent_name = null;
        }
        //新上级
        $new_agent_info = [];
        if($new_agent_id > 0){
            $new_agent_info = (array)\DB::table('user_agent')
                ->where('user_id',$new_agent_id)
                ->first(['id','user_id','uid_agent','uid_agent_name','uid_agents','level','inferisors_all']);
            if(!$user || !$new_agent_info){
                return $this->lang->set(10015);
            }
        }

        if($user['uid_agent'] == $new_agent_id){
            return $this->lang->set(10557);
        }
        //当前用户旧的所有上级
        $oldBeforeIds = explode(',',$user['uid_agents']);  //原上级  最后包括自己
        //保存规则是最后是自己  如  ： 一级,二级,三级,自己
        if($user_id != array_pop($oldBeforeIds)) {
            return $this->lang->set(10557);
        }
        //查新代理是否为曾经的下级
        $agent=new \Logic\User\Agent($this->ci);
        $currSubAgentIds = $agent->getAllSubAgentIds($user_id);
        if($new_agent_id > 0){
            if(in_array($new_agent_id,$currSubAgentIds)){  //不能移动到自己的下级
                return $this->lang->set(10557);
            }
        }

        //无上级
        if($new_agent_id == 0) {
            $agentIds =[];
            $newUidAgents = $user_id;
        }else{
            $agentIds = explode(',', $new_agent_info['uid_agents']); //移动后所有的上级
            $newUidAgents = $new_agent_info['uid_agents'] . ',' . $user_id;
        }

        $new_level_count = count($agentIds);
        $old_level_count = count($oldBeforeIds);
        //计算等级差，旧3级变新2级表示升1级，旧1级变新6级下表示降为第7级
        $levelDiff = $old_level_count>=$new_level_count ?  ($new_level_count - $old_level_count) : ($new_level_count);
        try {
            \DB::beginTransaction();
            //删除自己与上级的关系
            if($oldBeforeIds){
                \DB::table('child_agent')->where('cid', $user_id)->delete();
                if ($currSubAgentIds) {   //所有下级有需要删掉与当前移到用户的上级的关系
                    DB::table('child_agent')->whereIn('pid', $oldBeforeIds)->whereIn('cid', $currSubAgentIds)->delete();
                }
            }
            //新上级关系
            if($new_agent_id > 0){
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
                    'inferisors_all' => \DB::raw("inferisors_all - {$user['inferisors_all']}")
                ]);
            }

            //新上级的下级数
            if($new_agent_id > 0 && !empty($agentIds)){
                DB::table('user_agent')->whereIn('user_id', $agentIds)->update([
                    'inferisors_all' => \DB::raw("inferisors_all + {$user['inferisors_all']}")
                ]);
            }

            //更新下级等级及代理关系
            DB::table('user_agent')->whereIn('user_id', $currSubAgentIds)->update([
                'level' => \DB::raw("abs(level + {$levelDiff})"),
                'uid_agents' => \DB::raw("replace(uid_agents,'{$user['uid_agents']}','{$newUidAgents}')"),
            ]);

            //修改当前等级
            DB::table('user_agent')->where('user_id',$user_id)->update([
                'uid_agent' => $new_agent_id,
                'uid_agent_name' => $new_agent_name,
                'uid_agents' => $newUidAgents,
                'level' => \DB::raw("abs(level + {$levelDiff})"),
            ]);

            DB::commit();
            /*============================日志操作代码================================*/
            $uname = DB::table('user')
                ->where('id', '=', $user_id)
                ->value('name');
            (new Log($this->ci))->create($user_id, $uname, Log::MODULE_USER, '会员管理', '会员管理/会员详情/基本资料', '更换上级代理', 1, '更换上级代理 ['.$user['uid_agent_name'].']更改为['. $params['agent_name'].']');
            /*============================================================*/
            return $this->lang->set(0);
        }catch (Exception $e){

            print_r($e->getMessage());
            DB::rollback();
            return $this->lang->set(-2);
        }

    }



};
