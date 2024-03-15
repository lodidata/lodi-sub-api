<?php

use Logic\Admin\BaseController;
use Logic\Define\Cache3thGameKey;
use Logic\Wallet\Wallet as walletLogic;
use Logic\User\User as userLogic;
use Logic\GameApi\Common;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '用户详情：账户余额';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id'        => 'int(required) #用户id'
    ];
    
    const STATEs      = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS      = [];
    const SCHEMAS     = [
        ['map # channel: registe=网站注册, partner=第三方,reserved =保留'],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        //$user = DB::table('user')->find($id);
        $user = (new Common($this->ci))->getUserInfo($id);

        if(!$user)
            return $this->lang->set(10014);
        $third_account = DB::table('game_user_account')->where('user_id',$id)->value('user_account');
        $funds = DB::table('funds_child')
            ->selectRaw("uuid,balance,name,game_type,create_account")
            ->where('pid',$user['wallet_id'])
            ->get()->toArray();
        $tid = $this->ci->get('settings')['app']['tid'];
        $site_type = $website = $this->ci->get('settings')['website']['site_type'];
        if($site_type == 'ncg'){
            $jdb_account = $tid.'n'.$id;
        }else{
            $jdb_account = $tid.'o'.$id;
        }
        foreach ($funds as $fund){

            $fund->third_account = $third_account;
            if($fund->game_type == 'JDB'){

                $fund->third_account = $jdb_account.' -- '.$third_account;
            }elseif($fund->game_type == 'ALLBET'){
                $config = json_decode($this->redis->hget(Cache3thGameKey::$perfix['gameJumpUrl'], 'ALLBET'), true);
                $fund->third_account = $third_account . $config['lobby'].' -- '. $third_account;
            }
        }
        return (array)$funds;

    }


};
