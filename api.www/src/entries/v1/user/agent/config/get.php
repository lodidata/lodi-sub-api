<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '用户中心全局设置';
    const TAGS = "代理返佣";
    const SCHEMAS = [
        "agent_switch" => "boolean(required) #用户代理栏目开关",
        "lucky_switch" => "boolean(required) #幸运轮盘开关",
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        // 代理开关
        $data['agent_switch'] = \Model\User::where('id', $this->auth->getUserId())->value('agent_switch') ? true : false;

        //幸运轮盘开关
        $lucky_switch = DB::table('active')->select('status')->where('type_id', 6)->first();

        $data['lucky_switch'] = $lucky_switch && $lucky_switch->status == 'enabled' ? true: false;
        return $data;
    }
};