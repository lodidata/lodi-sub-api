<?php

namespace Logic\User;

use DB;

/**
 * 安全中心模块
 */
class Safety extends \Logic\Logic
{
    const TYPE = 1;

    public function getTools($userId)
    {
        $tools     = [];
        $safeCenter = \Model\SafeCenter::where('user_id', $userId)->first();
        $profile    = \Model\Profile::where('user_id', $userId)->first();
        $mobile     = \Model\Profile::getMobile($profile['mobile']);
        $email      = \Model\Profile::getEmail($profile['email']);
        if ($safeCenter['mobile'] == 1 && !empty($mobile)) {
            $mobile = [
                'mobile' => [
                    "id"     => 2,
                    "status" => 1,
                    "value"  => $mobile
                ]
            ];
            $tools  = array_merge($tools, $mobile);
        }

        if ($safeCenter['email'] == 1 && !empty($email)) {
            $email = [
                'email' => [
                    "id"     => 3,
                    "status" => 1,
                    "value"  => $email
                ]
            ];
            $tools = array_merge($tools, $email);
        }


        // if ($safeCenter['question'] == 1) {
        //     $answer = \Model\Answer::getRecords($userId);
        //     $answer = !empty($answer) ? current($answer) : [];
        //     if (!empty($answer)) {
        //         $answer = [
        //             'question' => [
        //                 "id"     => 4,
        //                 "status" => 1,
        //                 "value"  => $answer['question']
        //             ]
        //         ];
        //     }
        //     $tools  = array_merge($tools, $answer);
        // }

        return $tools;
    }

    /**
     * 安全中心列表
     *
     * @param int $id 用户id 或者代理id
     * @param int $type 类型(1：会员，2：代理)
     * @return array|mixed
     */
    public function getList($userId)
    {
        $type = 1;
        $info = \Model\SafeCenter::where('user_type', $type)->where('user_id', $userId)->first();


        if (empty($info)) {
            \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1]);
            $info = \Model\SafeCenter::where('user_type', $type)->where('user_id', $userId)->first();
        }

        $info = $info->toArray();
        $user = new \Logic\User\User($this->ci);
        $userInfo = $user->getInfo($userId);
        if ($info['id_card'] == 1) {
            $info['id_card_value'] = $this->lang->text("You have passed the real name authentication. ID number (%s)", [$userInfo['idcard']]);
        }

        if ($info['mobile'] == 1) {
            $info['mobile_value'] = $this->lang->text("Phone you verified:%s if it is lost or disabled, please replace it immediately to avoid account theft", [$userInfo['mobile']]);
        }
        if ($info['bank_card'] == 1) {
            $info['bank_card_value'] = $this->lang->text("You have bind the bank card");
        }

        if ($info['email'] == 1) {
            $email = $this->lang->text("Email you verified") . "：{$userInfo['email']}";
            $info['email_value'] = $email;
        }

        // if ($info['question'] == 1) {
        //     $info['question_value'] = '安全问题设置成功，请牢记问题答案哦。';
        // }

        // if ($info['dynamic'] == 1) {
        //     $info['dynamic_value'] = '动态密码设置成功。';
        // }

        return [$info, $userInfo];
    }

    public function sendAppsflyer($userId, $params)
    {
        //eventName 事件 充值 deposit 注册 register (大额充值 big_deposit 首充 first_deposit 这两个在充值里判断)
        switch ($params['eventName']) {
            case 'register':
                return $this->appsflyerRegister($userId, $params);
            case 'login_test':
                return $this->appsflyerLoginTest($params);
            case 'deposit':
                return $this->appsflyerDeposit($userId, $params);
        }
    }

    public function appsflyerRegister($userId, $params)
    {
        $res = $this->appsflyerPost($params);
        return $res;
    }

    public function appsflyerLoginTest($params)
    {
        $res = $this->appsflyerPost($params);
        return $res;
    }

    public function appsflyerDeposit($userId, $params)
    {
        //获取用户最近1小时充值成功的信息
        $time = date('Y-m-d H:i:s', (time() - 3600));
        $deposit_id = \DB::table('funds_deposit')
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->where('created', '>=', $time)
            ->value('id');

        if (!empty($deposit_id)) {
            //获取是否首充
            $count = \DB::table('funds_deposit')->where('user_id', $userId)->where('status', 'paid')->count();
            //分转元
            $params['amount'] =  bcdiv($params['amount'],100);
            $this->appsflyerPost($params);

            //首充
            if ($count == 1) {
                $params['eventName'] = 'first_deposit';
                $this->appsflyerPost($params);
            }

            //大于1000，大额充值
            if ($params['amount'] >= 1000) {
                $params['eventName'] = 'big_deposit';
                $this->appsflyerPost($params);
            }

        }
    }

    public function appsflyerPost($params)
    {
        try{
            $client = new \GuzzleHttp\Client();
            $data = [
                'eventName'    => $params['eventName'],
                'appsflyer_id' => (string)$params['appsflyer_id'],
            ];

            if(!empty($params['amount'])){
                $data['eventValue' ]= json_encode(['af_revenue' => $params['amount']]);
            }else{
                $data['eventValue' ]='';
            }
            $this->logger->error("statistics 发送第三方最终提交数据 ",array_merge($params,$data));

            $url = "https://api2.appsflyer.com/inappevent/{$params['app_id']}";
            $response = $client->request('POST', $url, [
                'body'    => json_encode($data),
                'headers' => [
                    'accept'            => 'application/json',
                    'content-type'      => 'application/json',
                    'authentication'    => $params['devKey'],
                ],
            ]);
            $result = $response->getBody();
            $this->logger->error("statistics 发送第三方返回数据 ".$result);
            return $result;
        }catch (\Exception $e){
            $this->logger->error("statistics ".$e->getMessage());
            return false;
        }
    }

    public function sendFirebase($userId, $params)
    {
        //充值 deposit 注册 register 首充 purchase
        switch ($params['eventName']) {
            case 'register':
                return $this->firebaseRegister($params);
            case 'login_test':
                return $this->firebaseLoginTest($params);
            case 'deposit':
                return $this->firebaseDeposit($userId, $params);
        }
    }

    public function sendAdjust($userId, $params)
    {
        //充值 deposit 注册 register 首充 purchase
        switch ($params['eventName']) {
            case 'register':
                return $this->adjustRegister($params);
            case 'login_test':
                return $this->adjustLoginTest($params);
            case 'deposit':
                return $this->adjustDeposit($userId, $params);
        }
    }

    public function firebaseRegister($params)
    {
        $params['events'] = [
            [
                "name" => "register"
            ]
        ];
        $res = $this->firebasePost($params);
        return $res;
    }

    public function firebaseLoginTest($params)
    {
        $params['events'] = [
            [
                "name" => "login_test"
            ]
        ];
        $res = $this->firebasePost($params);
        return $res;
    }

    public function firebaseDeposit($userId, $params)
    {
        //获取用户最近1小时充值成功的信息
        $time = date('Y-m-d H:i:s', (time() - 3600));
        $deposit_id = \DB::table('funds_deposit')
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->where('created', '>=', $time)
            ->value('id');

        if (!empty($deposit_id)) {
            $deposit_param = [
                "value"                 => $params['amount'],
            ];

            $params['events'] = [
                [
                    "name"   => "deposit",
                    "params" => $deposit_param
                ]
            ];

            //获取是否首充
            $count = \DB::table('funds_deposit')->where('user_id', $userId)->where('status', 'paid')->count();
            if ($count == 1) {
                $params['events'] = [
                    [
                        "name"   => "deposit",
                        "params" => $deposit_param
                    ],
                    [
                        "name"   => "purchase",
                        "params" => $deposit_param
                    ]
                ];
            }

            $this->firebasePost($params);

        }
    }

    public function firebasePost($params){
        $client = new \GuzzleHttp\Client();
        $data=[
            'app_instance_id' => $params['app_instance_id'],
            'events'          => $params['events'] ,
        ];
        !empty($params['fire_user_id']) && $data['user_id'] = $params['fire_user_id'];
        //测试地址  因为正式地址不会返回任何报错
        //$url = "https://www.google-analytics.com/debug/mp/collect?firebase_app_id={$params['firebase_app_id']}&api_secret={$params['api_secret']}";
        $url = "https://www.google-analytics.com/mp/collect?firebase_app_id={$params['firebase_app_id']}&api_secret={$params['api_secret']}";
        $response = $client->request('POST', $url, [
            'body'    => json_encode($data),
            'headers' => [
                'accept'            => 'application/json',
                'content-type'      => 'application/json',
            ],
        ]);
        return $response->getBody();

    }

    public function adjustRegister($params)
    {
        $event_token_json      = json_decode($params['event_token_json'], true);
        if(empty($event_token_json['register'])) throw new \Exception('adjust error: register event_token cannot be empty');
        $params['event_token'] = $event_token_json['register'];
        $res = $this->adjustPost($params);
        return $res;
    }

    public function adjustLoginTest($params)
    {
        $event_token_json      = json_decode($params['event_token_json'], true);
        if(empty($event_token_json['login_test'])) throw new \Exception('adjust error: login_test event_token cannot be empty');
        $params['event_token'] = $event_token_json['login_test'];
        $res = $this->adjustPost($params);
        return $res;
    }

    public function adjustDeposit($userId, $params)
    {
        //获取用户最近1小时充值成功的信息
        $time = date('Y-m-d H:i:s', (time() - 3600));
        $deposit_id = \DB::table('funds_deposit')
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->where('created', '>=', $time)
            ->value('id');

        if (!empty($deposit_id)) {
            $event_token_json      = json_decode($params['event_token_json'], true);
            if(empty($event_token_json['deposit'])) throw new \Exception('adjust error: deposit event_token cannot be empty');
            $params['event_token'] = $event_token_json['deposit'];

            $params["revenue"] = $params['amount'];
            $this->adjustPost($params);
            //获取是否首充
            $count = \DB::table('funds_deposit')->where('user_id', $userId)->where('status', 'paid')->count();
            if ($count == 1) {
                if(empty($event_token_json['first_deposit'])) throw new \Exception('adjust error: first_deposit event_token cannot be empty');
                $params['event_token'] = $event_token_json['first_deposit'];
                $this->adjustPost($params);
            }
        }
    }

    public function adjustPost($params){
        $client = new \GuzzleHttp\Client();
        $time = time();
        $url = "https://s2s.adjust.com/event?s2s=1&event_token={$params['event_token']}&app_token={$params['app_token']}&created_at_unix=$time";
        !empty($params['idfa']) && $str      = "&idfa={$params['idfa']}";
        !empty($params['gps_adid']) && $str  = "&gps_adid={$params['gps_adid']}";
        !empty($params['adid']) && $str      = "&adid={$params['adid']}";
        !empty($params['fire_adid']) && $str = "&fire_adid={$params['fire_adid']}";
        !empty($params['oaid']) && $str      = "&oaid={$params['oaid']}";
        !empty($params['revenue']) && $str   .= "&revenue={$params['revenue']}&currency=PHP&environment=production";
        $url .= $str;
        $this->logger->error("statistics 发送第三方最终提交数据 ",$params);
        $response = $client->request('POST', $url, [
            'headers' => [
                'Authorization'      => "Bearer {$params['api_token']}",
                ]
            ]);
        $result = $response->getBody();
        $this->logger->error("statistics 发送第三方返回数据 ".$result);
        return $result;
    }
    // /**
    //  * 获取身份证号码
    //  */
    // protected function getIdCard($userId) {
    //     $data = \Model\Profile::where('user_id', $userId)->first();
    //     $idcard = \Utils\Utils::RSADecrypt($data['idcard']);
    //     $idcard = '****' . substr($idcard, -4);
    //     $idcard = "你已通过实名认证。身份证号码($idcard)";
    //     return $idcard;
    // }

    // /**
    //  * 改变安全中心数据状态
    //  * @param int $user_id 用户id
    //  * @param int $field 要改变的字段名
    //  * @param int $value 要改变的字段值
    //  * @param int $type 类型(1:会员,2:代理)
    //  */
    // public function status($user_id, $field, $value, $type = self::TYPE)
    // {
    //     $user_id = (int)$user_id;
    //     if(empty($user_id) || empty($field))
    //     {
    //         return createRsponse($this->ci->response,400,15,'用户id或者字段名不能为空');

    //     }
    //     $info = DB::table('safe_center')->where('user_id',$user_id)->where('user_type',$type)->update([$field=>$value]);
    //     if($info >= 0)
    //     {
    //         return $this->ci->lang->set(0);
    //     }
    //     else
    //     {
    //         return createRsponse($this->ci->response,400,15,'改变状态失败');
    //     }
    // }


}
