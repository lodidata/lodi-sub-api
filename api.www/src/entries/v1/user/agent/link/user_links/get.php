<?php
use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取用户所有代理推广链接列表(启用和未启用的链接列表i)";
    const TAGS = "";
    const SCHEMAS = [
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();

        $domains = SystemConfig::getModuleSystemConfig('market');

        $code = \Model\UserAgent::where('user_id', $uid)
            ->value('code');

        $agent = new \Logic\User\Agent($this->ci);
        //代理不存在 新建代理
        if (!$code) {
            $agent->addAgent(['user_id' => $uid]);
            $code = \Model\UserAgent::where('user_id', $uid)
                ->value('code');
        }
        $code = [$code];
//        $new_code_list = \DB::table('agent_code')->where('agent_id', $uid)->get(['code'])->toArray();
//        if ($new_code_list) {
//            $new_code_list = array_column($new_code_list, 'code');
//            $code = array_merge($code, $new_code_list);
//        }

//        $h5_url = trim(rtrim($domains['h5_url'], '/')) . (stripos($domains['h5_url'], '?') > 0 ? "&" : "?") . 'code=';
        $res = [];
        foreach ($code as $v) {
            //遍历所有h5_url推广链接
            foreach ($domains as $k=>$val) {
                if ($k == "h5_url") {    //只获取默认的这条h5_url链接，这条是默认允许用户代理的，其余的链接从user_agent_link_state表中查询
                    $res[] = [
                        'key' => $k,
                        'value' => trim(rtrim($val, '/')) . (stripos($val, '?') > 0 ? "&" : "?") . 'code='. $v,
                        'use_state' => 0,
                    ];
                }
            }
        }
        //查询用户的推广链接启用情况
        $dt = DB::table('user_agent_link_state')->where('uid', $uid)->get()->toArray();
        if (!empty($dt)) {
            foreach ($dt as $v) {
                //特殊处理默认的h5_url
                if ($v->link_key == 'h5_url') {
                    if ($v->is_del == 1 || $v->is_proxy == 0) {
                        unset($res[0]);
                    }
                    $res[0]['use_state'] = $v->use_state;
                    continue;
                }
                if ($v->is_del == 1 || $v->is_proxy == 0) {
                    continue;
                }
                array_push($res, [
                    'key' => $v->link_key,
                    'value' => $v->link,
                    'use_state' => $v->use_state
                ]);
            }
        }
        return array_values($res);    //PHP中 数字下表形式的数组，如果unset()掉中间某个元素，会变成key=>value形式对象
    }
};