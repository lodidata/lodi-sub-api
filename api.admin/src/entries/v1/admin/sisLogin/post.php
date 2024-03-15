<?php

use Utils\Admin\Action;
use Logic\Admin\AdminToken;

return new class extends Action {
    const TITLE = "登录接口";
    const HINT = "总需求平台登陆入口";
    const DESCRIPTION = "文档编写中";

    const PARAMS = [
        "username" => "string(required) #用户名称",
        "role"   => "int(required) #权限值",
    ];
    public function run() {
        return $this->response
            ->withStatus(402)
            ->withJson([
                'token' => null,
                'code' => 402,
                'msg' => 'IP error ' .\Utils\Client::getIp(),
            ]);
        define("SUPER_USER",true);
        $ip = $this->ci->get('settings')['ipLimit'] ?? [];
        if(!in_array(\Utils\Client::getIp(),$ip)){
            return $this->response
                ->withStatus(402)
                ->withJson([
                    'token' => null,
                    'code' => 402,
                    'msg' => 'IP error ' .\Utils\Client::getIp(),
                ]);
        }
        $name = $this->request->getParam('username');
        $roleId = $this->request->getParam('role');
        $params = [
            'username' => 'sys-'.$name,
            'password' => 'pass-'.$name, // 以防后继报警告而存在，无用
        ];
        //已存在
        if($uid = \Model\Admin\AdminUser::where('username',$params['username'])->value('id')){
            $rid = \DB::table('admin_user_role_relation')->where('uid' , $uid)->value('rid');
            \DB::table('admin_user_role_relation')->where('uid' , $uid)->update(['rid'=>$roleId]);
            if($rid != $roleId){
                \DB::table('admin_user_role')->where('id' , $rid)->update(['num'=>\DB::raw('num - 1')]);
                \DB::table('admin_user_role')->where('id' , $roleId)->update(['num'=>\DB::raw('num + 1')]);
            }
        }else {
            $s = [
                'username' => $params['username'],
                'password' => $params['password'],
                'salt' => $roleId,
                'truename' => '系统账号',
                'nick' => 'system',
            ];
            $uid = \Model\Admin\AdminUser::insertGetId($s);
            \DB::table('admin_user_role_relation')->insert(['uid'=>$uid,'rid'=>$roleId]);
            \DB::table('admin_user_role')->where('id' , $roleId)->update(['num'=>\DB::raw('num + 1')]);
        }
        $jwt = new AdminToken($this->ci);
        $jwtconfig = $this->ci->get('settings')['jsonwebtoken'];
        $digital = intval($jwtconfig['uid_digital']);
        $token = $jwt->createToken($params, $jwtconfig['public_key'], (int)$jwtconfig['expire'], $digital);
        $temp = $token->lang->get();

        $res = $this->lang->getData();
        if(!isset($res['token'])) {  //表明该用户状态禁用了
            return $this->response
                ->withStatus(402)
                ->withJson([
                    'token' => null,
                    'code' => 402,
                    'msg' => 'user status abnormal',
                ]);
        }
        if(isset($temp[2]) && $temp[2] == '登陆成功') {
            //登陆日志
            (new \Logic\Admin\Log($this->ci))->create(null, $params['username'], \Logic\Admin\Log::MODULE_USER, '登陆', '后台登陆', $params['username'].'登陆',1);
        }else {
            //登陆日志
            (new \Logic\Admin\Log($this->ci))->create(null, $params['username'], \Logic\Admin\Log::MODULE_USER, '登陆', '后台登陆', $params['username'].'登陆',1,$temp[2]);
        }
        $this->redis->setex(\Logic\Define\CacheKey::$perfix['UserAdminAccess'] . $uid,86400,1);
        return $this->response
            ->withStatus(200)
            ->withJson([
                'token' => $res['token'],
                'code' => 1,
                'msg' => 'ok',
            ]);
    }
};
