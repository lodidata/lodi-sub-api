<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '人工存提首页列表';
    const DESCRIPTION = '';

    const QUERY       = [
        'role'     => 'enum[1,2](required) #1 用户，2 代理',
        'username' => 'string(required) #用户或代理的名称',
    ];

    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $username = $this->request->getParam('username');
        $result = ['secondary' => []];
        $total  = 0;
        if (!$username) {
            return $this->lang->set(10010);
        }
        $uid = \Model\User::where('name','=',$username)->value('id');
        if (!$uid) {
            return $this->lang->set(10014);
        }
        $result['uid'] = $uid;
        $user          = (new \Logic\User\User($this->ci))->getInfo($uid);
        $tagModel = \Model\Label::find($user['tags']);
        $tagName = "";
        if($tagModel){
            $tagName = $tagModel->title;
        }
        $levelName = '';
        if($user['ranting']){
            $levelName = \Model\UserLevel::where('level',$user['ranting'])->value('name');
        }
        $wid           = $user['wallet_id'];
//        $balance           = \Model\Funds::find($wid)->value('balance');
        $balance           = \Model\Funds::where('id','=',$wid)->first(['balance','share_balance','direct_balance']);

        $total             += $balance['balance'];
        $result['primary'] = [
            [
                'sid'     => $wid,
                'name'    => '主钱包',
                'balance' => $balance['balance']
            ]
        ];
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $secondaries = $wallet->getInfo($uid);
        //$secondaries['children']       = \Model\FundsChild::where('pid','=',$wid)->whereRaw('FIND_IN_SET("enabled",status)')->get()->toArray();
        foreach ( $secondaries['children']  as $secondary) {

            if($secondary['game_type'] == '主钱包'){
                continue;
            }

        }

        $need_pin_password = 0;
        $admin_pin_password_config = SystemConfig::getModuleSystemConfig('admin_pin_password');
        if(isset($admin_pin_password_config['status']) && $admin_pin_password_config['status'] == 1){
            $need_pin_password = 1;
        }

        $result['need_pin_password'] = $need_pin_password;
        $result['total_balance'] = $total;
        $result['tagName']=$tagName;
        $result['levelName']=$levelName;
        $result['username'] = $username;
        $result['share_balance']=$balance->share_balance;
        $result['direct_balance']=$balance->direct_balance;
        $dml = new \Logic\Wallet\Dml($this->ci);
        $tmp = $dml->getUserDmlData($uid);
        $result['dml'] = [
            'factCode' => $tmp->total_bet,
            'codes'    => $tmp->total_require_bet,
            'canMoney' => $tmp->free_money,
            'balance'  => \Model\User::getUserTotalMoney($uid)['lottery'] ?? 0 ,
        ];
        return $result;
    }
};
