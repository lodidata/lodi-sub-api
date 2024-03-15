<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\User\Agent as agentLgoic;
use lib\validate\RegisterValidate;
use Logic\User\User as userLogic;
return new class() extends BaseController
{
    const TITLE       = 'POST 后端个人中心注册(人人代理)';
    const DESCRIPTION = '提交会员注册';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'bkge_game'   => 'int(required) #电子',
        'bkge_live'   => 'int(required) #视频',
        'bkge_sport'   => 'int(required) #体育',
        'bkge_lottery'   => 'int(required) #彩票',
        'user_name'   => 'string(required) #用户',
        'password'   => 'string(required) #密码',
        'is_test' => 'int(required) #是否测试,0否，1是',
    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];


    public function run()
    {

        (new RegisterValidate())->paramsCheck('adminput',$this->request,$this->response);//注册参数校验

        $user_name    = $this->request->getParam('user_name');
        $password    = $this->request->getParam('password');
        $rake_back    = $this->request->getParam('rake_back');
        $is_test    = $this->request->getParam('is_test');

        $bkge = \Logic\Set\SystemConfig::getModuleSystemConfig('rakeBack');
        unset($bkge['agent_switch']);
        $user_bkge = [];
        foreach ($bkge as $key => $val) {
            if(isset($junior[$key])) {
                if($rake_back[$key] > $val) {
                    return $this->lang->set(551);
                }
                $user_bkge[$key] = $rake_back[$key];
            }
        }
        $user = new \Logic\User\User($this->ci);
        $logs = new \Model\Admin\LogicModel();
        $logs->logs_type = '新增/添加';
        $logs->opt_desc = '用户名('.$user_name.')';
        $logs->setTarget(0,$user_name);
        $logs->log();
        return $user->register($user_name, $password, '',json_encode($user_bkge),$is_test);

    }


};