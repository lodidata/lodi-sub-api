<?php

use Utils\Www\Action;
use Model\UserAgent;
use Model\User;
use Logic\Set\SystemConfig;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "会员中心-获取会员绑定的银行卡列表";
    const TAGS = "银行卡";
    const SCHEMAS = [
        "type" => "int(required) #身份证状态（1：有，0：没有）",
        "withdraw_pwd" => "int(required) #取款密码（1：有，0：没有）",
        "list" => [
            [
                'id' => 'int(required) #ID',
                "name" => "string(required) #户名",
                "account" => "string(required) #银行账号",
                "bank_name" => "string(required) #银行名称",
                "short_name" => "string(required) #银行英文简称",
                "deposit_bank" => "string(required) #开户支行",
                "time" => "string(required) #绑定时间 2012-08-12 12:23:32",
                "updated" => "string() #更新时间  2012-08-12 12:23:32",
                "shortname" => "string(required) #银行简称",
                "state" => "string(required) #银行状态 disabled,enabled,delete 默认enabled",
                "h5_logo" => "string() #h5 logo图片",
                'logo' => "string() #PC logo图片"
            ]
        ],
        "del_list" => [
            [
                'id' => 'int(required) #ID',
                "name" => "string(required) #户名",
                "account" => "string(required) #银行账号",
                "bank_name" => "string(required) #银行名称",
                "short_name" => "string(required) #银行英文简称",
                "deposit_bank" => "string(required) #开户支行",
                "time" => "string(required) #绑定时间 2012-08-12 12:23:32",
                "updated" => "string() #更新时间  2012-08-12 12:23:32",
                "shortname" => "string(required) #银行简称",
                "state" => "string(required) #银行卡状态 disabled,enabled,delete 默认enabled",
                "h5_logo" => "string() #h5图片",
                'logo' => "string() #PC图片"
            ]
        ],
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $data = [];
        $newBankList = [];
        $newDelList = [];
        //是否加密显示
        $isShow = $this->request->getParam('isShow', 0);
        // return ['code' => \Model\BankUser::where('user_id', $this->auth->getUserId())->first() ? 0 : 1];
        // $bankList = \Model\BankUser::where('role', 1)->where('user_id', $this->auth->getUserId())->get()->toArray();
        $userId = $this->auth->getUserId();
        $bankList = \Model\BankUser::getRecords($userId, $isShow);
        $safety = new \Logic\User\Safety($this->ci);

        // 银行卡限额
        $withdrawCardLimit = SystemConfig::getModuleSystemConfig('withdraw')['withdraw_card_money'];
        // 提现限额
        $withdrawLimit = SystemConfig::getModuleSystemConfig('withdraw')['withdraw_money'];

        //获取用户等级信息
        $userData = User::where('id', $userId)->select(['ranting'])->first()->toArray();
        $bankCardSum = DB::table('user_level')->where('level', $userData['ranting'])->select(['bankcard_sum','welfare'])->first();
        if ($bankCardSum) $welfare = $bankCardSum->welfare;
        if (!empty($welfare)) $welfare = json_decode($welfare,true);

        if (!empty($bankList)) {
            foreach ($bankList as $k => $val) {
                $val = (array)$val;
                $temp = $val;
                $temp = isset($temp) ? $temp : [];
                unset($temp['status']);
                if (!empty($val['status'])) {
                    $status = explode(',', $val['status']);
                    if (in_array('online', $status)) {
                        $temp['online'] = 1;
                    } else {
                        $temp['online'] = 0;
                    }
                }
                // 银行code为Gcash显示提现限额，其余显示银行卡限额
                if(in_array($temp['short_name'], ['Paymaya Philippines, Inc.', 'Gcash'])){
                    $temp['withdraw_max'] = $welfare['withdraw_max'] ?? 0;
                    $temp['withdraw_min'] = $welfare['withdraw_min'] ?? 0;
                }else{
                    $temp['withdraw_max'] = $welfare['bank_withdraw_max'] ?? 0;
                    $temp['withdraw_min'] = $welfare['bank_withdraw_min'] ?? 0;
                }
                $temp['bank_name'] = $this->lang->text($temp['short_name']);
                $temp['logo'] = showImageUrl($temp['logo']);
                if (strpos($val['state'], 'disabled') === false && strpos($val['state'], 'delete') === false) {
                    $newBankList[] = $temp;
                } else {
                    $newDelList[] = $temp;
                }
            }
        }


        $levelData = DB::table('user_level')
            ->where('level', '>', $userData['ranting'])
            ->where('bankcard_sum', '>', $bankCardSum->bankcard_sum)
            ->first();
        if (empty($levelData)) {
            $data['bindcard_msg'] = "";
        } else {
            $data['bindcard_msg'] = $this->lang->text('Next level able to add bank card account', [$levelData->name, $levelData->bankcard_sum]);
        }

        //判断用户是否可以继续绑卡
        $data['bind'] = 0;
        if (intval($bankCardSum->bankcard_sum) - count($newBankList) > 0) {
            $data['bind'] = 1;
        }

        $bkgeConfig = SystemConfig::getModuleSystemConfig('withdraw')['withdraw_bkge_money'];

        $code = UserAgent::getCode($userId);
        list($safetyInfo, $userInfo) = $safety->getList($userId);
        $data['type'] = $safetyInfo['id_card'];
        $data['code'] = $code;
        $data['withdraw_pwd'] = (int)$safetyInfo['withdraw_password'];
        // $data['name'] = $user['true_name'];
        $data['list'] = array_values($newBankList ? $newBankList : []);
        $data['del_list'] = array_values($newDelList ? $newDelList : []);
        $data['withdraw_bkge_min'] = $bkgeConfig['withdraw_min'] ?? 0;              //盈亏返佣提现限额
        $data['withdraw_bkge_max'] = $bkgeConfig['withdraw_max'] ?? 999999900;

        return $data;
    }
};