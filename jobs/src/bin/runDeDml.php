<?php
$active = \DB::table('active')->where('type_id',9)->where('status', 'enabled')->value('id');
if(empty($active)){
    return;
}
$funds_withdraw_list = \DB::table('active_apply')->selectRaw('user_id,coupon_money,user_name')
    ->where('created','>=','2023-03-28')->where('active_id',$active)->get()->toArray();


if($funds_withdraw_list){
    foreach($funds_withdraw_list as $val){
        $dml = bcmul($val->coupon_money,4);
        if(empty($val->user_id)) continue;
        new_de_dml($val->user_id, (int)$dml,$val->user_name);
    }
}

function new_de_dml($user_id, $withdraw_bet,$user_name) {
    global $app;
    $ci = $app->getContainer();
    $userId = $user_id;
    $withdrawBet = -$withdraw_bet;
    $date = date('Y-m-d H:i:s');

    \DB::table('dml_manual')
        ->insert(['user_id' => $userId, 'withdraw_bet' => $withdrawBet, 'created' => $date]);


    //流水里面添加打码量可提余额等信息
    $dml = new \Logic\Wallet\Dml($ci);
    $dmlData = $dml->getUserDmlData($userId, $withdrawBet, 2);



    $LogicModel = new \Model\Admin\LogicModel;
    $LogicModel->setTarget($userId,$user_name);
    $LogicModel->logs_type = $withdrawBet > 0 ? '增加打码量' : '减少打码量';
    $withdrawBet = $withdrawBet/100;
    $LogicModel->opt_desc = '打码量('.$withdrawBet.')';
    $LogicModel->log();
    return [];
}

