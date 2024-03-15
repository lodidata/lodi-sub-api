<?php

namespace Logic\GameApi;

use Utils\DesEncryptService;

class GameToken
{
    public function getToken($userId){
        $token_params = [
            'id'       => $userId,
            'type'     => 'game_token',
            'expire'   => 3600*24,
        ];

        $token = (new DesEncryptService())->getToken($token_params);
        return $token;
    }

    public function getUserId($account){
        global $app;
        $ci = $app->getContainer();
        $redis_key = 'game_user_id:'.$account;
        $user_id = $ci->redis->get($redis_key);
        if(!$user_id){
            $tid = $ci->get('settings')['app']['tid'];
            $user_id = \DB::table('game_user_account')->where('user_account', $account)->value('user_id');
            if(!$user_id){
                //tid+o+user_id
                $pre = substr($account, 0, 3);
                if($pre == $tid . 'n' || $pre == $tid . 'o' || $pre == $tid . 'i'){
                    $user_id = intval(str_replace($pre, '',$account));
                }
            }
            if($user_id){
                $ci->redis->setex($redis_key, 3600*24, $user_id);
            }
        }
        return $user_id;
    }

}