<?php

use Logic\Admin\BaseController;
use Logic\Wallet\Wallet as walletLogic;
use Logic\User\User as userLogic;
return new class() extends BaseController
{
    const TITLE       = '获取第三方余额';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id'        => 'int(required) #用户id',
        'type'      => '第三方游戏类型',
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
        list($freeMoney, $totalMoney) = $gameClass->getThirdBalance();
        $wid = \Model\User::where('id',$id)->value('wallet_id');
        //有时为null
        empty($totalMoney) && $totalMoney = 0;
        \Model\FundsChild::where('pid',$wid)->where('game_type',$params['game_type'])->update(['balance' => $totalMoney]);
        return $this->lang->set(0, [], [
            'freeMoney' => $freeMoney,  // 可下分金额
            'balance' => $totalMoney    // 总金额
        ]);

    }


};
