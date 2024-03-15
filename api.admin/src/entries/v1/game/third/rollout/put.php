<?php

use Logic\Admin\BaseController;
use Logic\Wallet\Wallet as walletLogic;
use Logic\User\User as userLogic;
return new class() extends BaseController
{
    const TITLE       = '第三方余额转出到主钱包';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id'        => 'int(required) #用户id',
        'game_type'      => '第三方',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id,'用户');

        (new \lib\validate\BaseValidate(
            [
                'game_type'=>'require'
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        $gameClass = \Logic\GameApi\GameApi::getApi($params['game_type'], $id);
        $lock_key = \Logic\Define\Cache3thGameKey::$perfix['gameBalanceRollOut']. 'lock_' . $id;
        $this->redis->del($lock_key);
        $lock_key = \Logic\Define\Cache3thGameKey::$perfix['gameBalanceLockUser'] . $id;
        $this->redis->del($lock_key);
        return $gameClass->rollOutThird();

    }


};
