<?php

use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE       = '修改下级代理返佣比例';
    const DESCRIPTION = '修改下级代理返佣比例';
    const TAGS = "代理返佣";
    const PARAMS    = [
        'id'        => 'int(required) #下级会员id',
        'bkge_game' =>'int() #游艺电子退佣比例',
        'bkge_live' =>'int() #视频退佣比例',
        'bkge_sport'=>'int() #体育退佣比例',
        'bkge_lottery'=>'int() #彩票退佣比例',
        'bkge_fish'=>'int() #捕鱼退佣比例',
    ];

    const SCHEMAS     = [

    ];


    public function run($id)
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $id;
        $agent = new \Logic\User\Agent($this->ci);
        //$uid = $this->request->getParam('id');
        if(!is_numeric($uid)){
           return $this->lang->set(2090);
        }
        $uid_agent = $this->auth->getUserId();
        $verify = $agent->isHigherLevel($uid_agent, $uid);//验证是否有权限查看
        if (!$verify->allowNext()) {
            return $verify;
        }
        $message = $this->request->getParams();

        $rake_agent = [
            'GAME'  =>    (int)($message['bkge_game'] ?? 0),
            'LIVE'  =>    (int)($message['bkge_live'] ?? 0),
            'SPORT' =>    (int)($message['bkge_sport'] ?? 0),
            'CP'    =>    (int)($message['bkge_lottery'] ?? 0),
            'BY'    =>    (int)($message['bkge_fish'] ?? 0),
        ];
        $rake_agent = json_encode($rake_agent);
        $allow      = $agent->allow($rake_agent,'',$uid_agent,$uid);
        if ($allow->getState()) {
            return $allow;
        }

        \Model\UserAgent::where('user_id', $uid)->update(['bkge_json' => $rake_agent]);
        $user = (new \Logic\User\User($this->ci))->getInfo($this->auth->getUserId());;
        $bkge_log = ['user_id'=>$uid, 'opt_id'=>$uid_agent, 'opt_type'=>2, 'opt_name'=>$user['user_name']];
        return $this->lang->set(0);
    }
};
