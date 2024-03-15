<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理推广链接";
    const TAGS = "";
    const SCHEMAS = [
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $domains = SystemConfig::getModuleSystemConfig('market');
        $code    = \Model\UserAgent::where('user_id', $uid)
            ->value('code');

        $agent = new \Logic\User\Agent($this->ci);
        //代理不存在 新建代理
        if (!$code) {
            $agent->addAgent(['user_id' => $uid]);

            $code = \Model\UserAgent::where('user_id', $uid)
                ->value('code');
        }
        $code = [$code];
//        $new_code_list = \DB::table('agent_code')->where('agent_id',$uid)->get(['code'])->toArray();
//        if($new_code_list){
//            $new_code_list = array_column($new_code_list,'code');
//            $code          = array_merge($code, $new_code_list);
//        }

        $h5_url = trim(rtrim($domains['h5_url'], '/')) . (stripos($domains['h5_url'], '?')>0 ? "&" : "?").'code=';
        $res = [];
        foreach ($code as $v){
            array_push($res, $h5_url.$v);
        }
        return $res;
    }
};