<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "流水统计";
    const TAGS = "流水统计";
    const SCHEMAS = [ ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $agent = new \Logic\User\Agent($this->ci);
        $params = $this->request->getParams();
        $type = $params['type'] ?? 1;
        $time = $params['time'] ?? date('Y-m-d', time());

        //总流水统计
        $bet_list = $agent->betStatic($uid, $type, $time);
        if(empty($bet_list)){
            $bet_list = [];
        }

        return $bet_list;
    }
};