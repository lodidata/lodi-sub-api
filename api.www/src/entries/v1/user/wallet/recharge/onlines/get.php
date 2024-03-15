<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = 'GET 获取线上类型';
    const TAGS = "充值提现";
    const SCHEMAS = [
            'money' => [
                'min_money' => 'int #该类型金额支持最小值',  //分
                'max_money' => 'int #该类型金额支持最大值'  //分
            ],
            'type'  => [
                'id'        => 'int #ID',
                'd_title'   => 'string #类型描述',
                'name'      => 'string #名称',
                'imgs'      => 'string #图片地址',
                'min_money' => 'int #该类型金额支持最小值',  //分
                'max_money' => 'int #该类型金额支持最大值'  //分
            ],
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $pay = new \Logic\Recharge\Pay($this->ci);

        $user = (new \Logic\User\User($this->ci))->getInfo($this->auth->getUserId());
        $userLevel = $user['ranting'];  //用户层级
        $onlineRecharges = $pay->getOnlineChannel($userLevel);
        if($onlineRecharges['type']){
            foreach ($onlineRecharges['type'] as $key => $conf){
                if($conf['type'] == 'autotopup'){
                    unset($onlineRecharges['type'][$key]);
                }
                $onlineRecharges['type'][$key]['imgs'] = showImageUrl($onlineRecharges['type'][$key]['imgs']);
            }
        }

        return $onlineRecharges;
    }
};
