<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "返佣比例";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [
    ];
    const SCHEMAS = [
        [
            "day"           =>"dateTime(required) #时间 2019-01-15 00:00:00",
            "bet_amount"    => "int(required) #投注额 214",
            "bkge"          => "int(required) #返佣金额-- 单位分 345",
            "user_bake"     => "int(required) #返佣次数 12",
            "status"        => "string(required) #是否返佣 1 已返  0 未返",
        ]
    ];


    public function run(){
        /*$verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $bkge_json = \DB::table('user_agent')->where('user_id', $uid)->value('bkge_json');
        $res       = $bkge_json ? json_decode($bkge_json, true) : [];
        if($res){
            $new_res = [];
            unset($res['agent_switch']);
            $i = 0;
            foreach ($res as $k => $v){
                $key = strtolower($k).' commission';
                $new_res[$i]['name']  = $this->lang->text($key);
                $new_res[$i]['value'] = $v.'%';
                $i++;
            }
            $res = $new_res;
        }*/
        // 查询代理配置数据
        $userAgentConf = SystemConfig::getModuleSystemConfig('rakeBack');
        unset($userAgentConf['agent_switch']);
        $i = 0;
        foreach ($userAgentConf as $k => $v){
            $key = strtolower($k).' commission';
            $new_res[$i]['name']  = $this->lang->text($key);
            $new_res[$i]['value'] = $v.'%';
            $i++;
        }
        $res = $new_res;

        return $this->lang->set(0, [], $res);
    }
};