<?php

namespace Logic\User;

use Logger;

class StatisticsRegDep extends \Logic\Logic
{
    //记录充值、注册
    public function sendStatisticsRegDep($params)
    {
        if(!empty($params['firebase_app_id'])){
            return $this->firebase($params);
        }elseif(!empty($params['appsflyer_id'])){
            return $this->appsflyer($params);
        }elseif(!empty($params['app_token'])){
            return $this->adjust($params);
        }
    }

    public function appsflyer($params){
        $insertData = [
            'user_id' => $params['user_id'],
            'app_id' => $params['app_id'],
            'dev_key' => $params['dev_key'],
            'appsflyer_id' => $params['appsflyer_id'],
        ];

        //注册的时候 在表里插入数据
        if (in_array($params['eventName'],['register','login_test'])) {
            $count= \DB::table('user_register_deposit_log')->where('user_id' ,$params['user_id'])->count();
            !$count && \DB::table('user_register_deposit_log')->insert($insertData);
        }

        $safety = new Safety($this->ci);
        $onParams = [
            'appsflyer_id' => $params['appsflyer_id'],
            'app_id' => $params['app_id'],
            'devKey' => $params['dev_key'],
            'eventName' => $params['eventName'],
            'amount' => $params['amount'] ?? 0,
        ];
        $safety->sendAppsflyer($params['user_id'], $onParams);

        return true;
    }

    public function firebase($params){

        $insertData = [
            'user_id'           => $params['user_id'],
            'firebase_app_id'   => $params['firebase_app_id'],
            'api_secret'        => $params['api_secret'],
            'app_instance_id'   => $params['app_instance_id'],
            'fire_user_id'      => $params['fire_user_id'],
        ];

        //注册的时候 在表里插入数据
        if (in_array($params['eventName'],['register','login_test'])) {
            $count= \DB::table('user_firebase')->where('user_id' ,$params['user_id'])->count();
            !$count && \DB::table('user_firebase')->insert($insertData);
        }

        $safety = new Safety($this->ci);
        $onParams = [
            'firebase_app_id' => $params['firebase_app_id'],
            'api_secret'      => $params['api_secret'],
            'app_instance_id' => $params['app_instance_id'],
            'fire_user_id'    => $params['fire_user_id'],
            'eventName'       => $params['eventName'],
            'amount'          => $params['amount'] ?? 0,
        ];
        $safety->sendFirebase($params['user_id'], $onParams);

        return true;
    }

    public function adjust($params){
        $insertData = [
            'user_id'        => $params['user_id'],
            'app_token'      => $params['app_token'],
            'api_token'      => $params['api_token'],
            'event_token_json'      => $params['event_token_json'],
        ];

        !empty($params['idfa']) && $insertData['idfa']              = $params['idfa'];
        !empty($params['gps_adid']) && $insertData['gps_adid']      = $params['gps_adid'];
        !empty($params['adid']) && $insertData['adid']              = $params['adid'];
        !empty($params['fire_adid']) && $insertData['fire_adid']    = $params['fire_adid'];
        !empty($params['oaid']) && $insertData['oaid']              = $params['oaid'];

        //注册的时候 或者测试登录 在表里插入数据
        if (in_array($params['eventName'],['register','login_test'])) {
            $count= \DB::table('user_adjust')->where('user_id' ,$params['user_id'])->count();
            !$count && \DB::table('user_adjust')->insert($insertData);
        }

        $safety = new Safety($this->ci);

        $onParams = [
            'app_token'        => $params['app_token'],
            'api_token'        => $params['api_token'],
            'eventName'        => $params['eventName'],
            'amount'           => $params['amount'] ?? 0,
            'event_token_json' => $params['event_token_json'],
        ];
        !empty($params['idfa']) && $onParams['idfa']              = $params['idfa'];
        !empty($params['gps_adid']) && $onParams['gps_adid']      = $params['gps_adid'];
        !empty($params['adid']) && $onParams['adid']              = $params['adid'];
        !empty($params['fire_adid']) && $onParams['fire_adid']    = $params['fire_adid'];
        !empty($params['oaid']) && $onParams['oaid']              = $params['oaid'];

        $safety->sendAdjust($params['user_id'], $onParams);

        return true;
    }
}
