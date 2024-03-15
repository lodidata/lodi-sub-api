<?php

use Utils\Www\Action;
use Model\Active;
use lib\validate\BaseValidate;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '提交用户申请';
    const TAGS = "优惠活动";
    const QUERY = [
        'id' => 'int(required) #活动ID',
        'reason' => 'varchar(required) #申请理由',
        'apply_pic' => 'varchar(required) #申请凭证',
    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validate = new BaseValidate([
            'id' => 'require',
        ]);

        $validate->paramsCheck('', $this->request, $this->response);

        //判断用户申请条件
        $activeId = $this->request->getParam('id') ? $this->request->getParam('id') : $this->request->getQueryParam('id');
        $userId = $this->auth->getUserId();
        //设置频率限制redis_key
        $lock_key = md5("apply_" . $userId . '_' . $activeId);
        //用户申请请求频率限制
        if ($this->redis->incr($lock_key) > 1) {
            return $this->lang->set(885);
        }

        $this->redis->expire($lock_key, 4);

        $user = (new \Logic\User\User($this->ci))->getUserInfo($userId);
        $activeRule = (array) DB::table('active')
            ->where('id', $activeId)
            ->selectRaw('send_times,apply_times,condition_recharge,condition_user_level,name')
            ->first();

        $user_black = DB::table('active_apply_blacklist')->where('active_id', $activeId)->where('user_id', $userId)->count();
        if ($user_black){
            return $this->lang->set(208);
        }

        if ($activeRule['condition_recharge']) {
            switch ($activeRule['condition_recharge']) {
                case 2: //当日是否有充值
                    $rechargeStart = date('Y-m-d 00:00:00');
                    $rechargeEnd = date('Y-m-d 23:59:59');
                    break;
                case 3: //本周是否有充值
                    $rechargeStart = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
                    $rechargeEnd = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
                    break;
                case 4: //本月是否有充值
                    $rechargeStart = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y")));
                    $rechargeEnd = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("t"), date("Y")));
                    break;
            }
            //线上线下充值，手动存款，手动增加余额都算
            if($activeRule['condition_recharge'] == 1){
                $rechargeCount = DB::table('funds_deposit')->where(['user_id' => $userId, 'status' => 'paid'])->count() +
                DB::table('funds_deal_manual')->where(['user_id' => $userId, 'status' => 1, 'type' => ['in', [1, 5]]])->count(); 
            }else{
                $rechargeCount = DB::table('funds_deposit')->where(['user_id' => $userId, 'status' => 'paid'])->whereBetween('created', [$rechargeStart , $rechargeEnd])->count() +
                DB::table('funds_deal_manual')->where(['user_id' => $userId, 'status' => 1, 'type' => ['in', [1, 5]]])->whereBetween('created', [$rechargeStart , $rechargeEnd])->count();
            }
            if ($rechargeCount < 1) {
                return $this->lang->set(4200);
            }
        }

        if ($activeRule['condition_user_level']) { //用户等级条件
            $userLevel = $user['ranting'];
            if (!in_array($userLevel, explode(',', $activeRule['condition_user_level']))) {
                return $this->lang->set(4200);
            }
        }

        if ($activeRule['send_times'] == 1) { //活动赠送次数为一个用户一次时，如果该用户已经领取过该活动，不能再次申请
            $receiveCount = DB::table('active_apply')->where(['active_id' => $activeId, 'user_id' => $userId, 'status' => 'pass'])->count();
            if ($receiveCount) {
                return $this->lang->set(4200);
            }
        }

        if (!empty($activeRule['apply_times'])) {
            $applyCount = DB::table('active_apply')
                ->where(['active_id' => $activeId, 'user_id' => $userId, 'apply_count_status' => 1, ['created', '>=', date("Y-m-d") . " 00:00:00"], ['created', '<=', date("Y-m-d") . " 23:59:59"]])
                ->count(); //判断活动最大申请次数
            if ($applyCount + 1 > $activeRule['apply_times']) {
                return $this->lang->set(4200);
            }
        }

        $reason = $this->request->getParam('reason');
        $applyPic = $this->request->getParam('apply_pic');

        // 写入活动申请表
        $model_active = [
            'user_id' => $userId,
            'user_name' => $user['name'] ?? $user['user_name'],
            'active_id' => $activeId,
            'active_name' => $activeRule['name'],
            'memo' => $activeRule['name'],
            'status' => 'undetermined',
            'state' => 'manual',
            'reason' => $reason,
            'apply_pic' => $applyPic,
            'apply_time' => date('Y-m-d H:i:s', time()),
        ];
        $res = \DB::table('active_apply')->insertGetId($model_active);
        if ($res) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};