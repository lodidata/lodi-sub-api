<?php

use Logic\Admin\BaseController;
use lib\validate\admin\RoomValidate;
use Logic\Admin\Log;
use Logic\GameApi\Common;
use Model\LotteryOrder;

return new class() extends BaseController {
    const TITLE = '操作打码量';
    const DESCRIPTION = '接口';
    
    const QUERY = [

    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        $userId = $params['user_id'];
        $withdrawBet = $params['withdraw_bet'];
        $date = date('Y-m-d H:i:s');

        \DB::table('dml_manual')
           ->insert(['user_id' => $userId, 'withdraw_bet' => $withdrawBet, 'created' => $date]);

        //插入流水记录
        $user = (new Common($this->ci))->getUserInfo($userId);

        $funds_balance = \Model\Funds::where('id', $user['wallet_id'])->value('balance');

        //流水里面添加打码量可提余额等信息
        $dml = new \Logic\Wallet\Dml($this->ci);
        $dmlData = $dml->getUserDmlData($userId, $withdrawBet, 2);

        if(isset($GLOBALS['playLoad'])) {
            $admin_id = $GLOBALS['playLoad']['uid'];
            $admin_name = $GLOBALS['playLoad']['nick'];
        }else {
            $admin_id = 0;
            $admin_name = '';
        }
        $deal_number = LotteryOrder::generateOrderNumber();
        $order_number = LotteryOrder::generateOrderNumber();
        \Model\FundsDealLog::create([
            "user_id"           => $userId,
            "user_type"         => 1,
            "username"          => $user['name'],
            "deal_number"       => $deal_number,
            'order_number'      => $order_number,
            "deal_type"         => $withdrawBet > 0 ? 405 : 406,
            "deal_category"     => 1,
            "deal_money"        => 0,
            "balance"           => intval($funds_balance),
            "memo"              => '',
            "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
            'total_bet'         => $dmlData->total_bet,
            'withdraw_bet'      => $withdrawBet,
            'total_require_bet' => $dmlData->total_require_bet,
            'free_money'        => $dmlData->free_money,

            'admin_id'=>$admin_id,
            'admin_user'=>$admin_name,
        ]);
        if(empty($params['memo'])){
            $params['memo'] = $withdrawBet > 0 ? '厅主后台手动增加打码量' : '厅主后台手动减少打码量';
        }
        // 增加手动入款记录
        \Model\FundsDealManual::create([
            'user_id'       => $userId,
            'username'      => $user['name'],
            'user_type'     => 1,
            'type'          => $withdrawBet > 0 ? 9 : 10,
            'trade_no'      => $order_number,
            'operator_type' => 1,
            'front_money'   => intval($funds_balance),
            'money'         => 0,
            'balance'       => intval($funds_balance),
            'admin_uid'     => $admin_id,
            'wallet_type'   => 1,
            'memo'          => $params['memo'],
            'withdraw_bet'  => abs($withdrawBet),
        ]);

        $LogicModel = new Model\Admin\LogicModel;
        $LogicModel->setTarget($userId,$user['name']);
        $LogicModel->logs_type = $withdrawBet > 0 ? '增加打码量' : '减少打码量';
        $withdrawBet = $withdrawBet/100;
        $LogicModel->opt_desc = '打码量('.$withdrawBet.')';
        $LogicModel->log();
        return [];
    }
};
