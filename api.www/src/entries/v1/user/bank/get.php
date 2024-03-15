<?php

use Utils\Www\Action;
use Model\Bank;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "会员中心-获取银行列表";
    const TAGS = "银行卡";
    const SCHEMAS = [
        'list' => [
            [
                "id" => "int(required) # 银行id",
                "name" => "string(required) # 银行名称",
                "code" => "string(required) #银行英文简称",
                "state" => "string(required) #银行状态 disabled,enabled,delete 默认enabled",
                "img" => "string() #银行logo图片",
                "logo" => "string() #银行logo图片",
            ]
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }


        $res = Bank::where('type', 1)
                ->whereRaw("FIND_IN_SET('enabled', status)")
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'asc')
                ->selectRaw('id, name, code, h5_logo as img, status, logo')
                ->get()
                ->toArray();

        global $app;
        $ci = $app->getContainer();
        $country = $ci->get('settings')['website']['site_type'];

        foreach ($res as &$val) {
            if($country != 'ngn'){
                $val['name'] = $this->lang->text($val['code']);
            }
            $val['img'] = showImageUrl($val['img']);
            $val['logo'] = showImageUrl($val['logo']);
        }
        unset($val);
        return ['list' => $res];
    }
};