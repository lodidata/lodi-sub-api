<?php

use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "获取小米订单号";
    const TAGS = "充值提现";
    const QUERY = [
        'money' => "int(required) #订单金额",
    ];
    const SCHEMAS = [
        'orderNumber'  => "string(required) #订单号",
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();

        $money = $this->request->getParam('money', '');
        if ($money == '') {
            return $this->lang->set(886, [$this->lang->text("Amount cannot be empty!")]);
        }

        $userData = (array)DB::table('user')->where('id', $userId)->get(['name', 'role'])->first();
        if(!$userData){
            return $this->lang->set(886, [$this->lang->text("User does not exist!")]);
        }

        $times = DB::table('system_config')->where('module', '=', 'withdraw')->where('key', '=', 'withdraw_money_times')->value('value');
        if ($userData['role'] == 'rotot' && $times) {
            $times = explode(':', $times)[1];
            $money = $money * $times;
        }

        $rand = random_int(10000, 99999);
        $orderNumber =  date('Ymdhis') . str_pad(random_int(1, $rand), 4, '0', 0);

        $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';

        $res = \DB::table('funds_deposit')
            ->insert([
                'user_id' => $userId,
                'trade_no' => $orderNumber,
                'money' => $money,
                'origin' => isset($origins[$origin]) ? $origins[$origin] : 0,
                'name' => $userData['name'],
                'ip' => \Utils\Client::getIp(),
                'memo' => $this->lang->text("Xiaomi pays orders")
            ]);
        if ($res !== false) {
            return $this->lang->set(0, [], ['orderNumber' => $orderNumber]);
        }

        return $this->lang->set(-2);

    }
};