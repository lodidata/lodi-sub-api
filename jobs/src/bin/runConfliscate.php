<?php
//批量没收提款订单
/*$funds_withdraw_list = \DB::table('funds_withdraw')->selectRaw('id')
    ->where('created','>=','2023-03-17')->where('status','pending')->get()->toArray();

global $app;
$ci = $app->getContainer();
if($funds_withdraw_list){

    foreach($funds_withdraw_list as $val){
        $comment = 'confiscate active';
        new_item_run($val->id, 'confiscate', $comment);
    }
    
}
function new_item_run($id, $status, $comment) {
$admin_id = 0;
$admin_name = '';
    global $app;
    $ci = $app->getContainer();
/*try {
    $ci->db->getConnection()
        ->beginTransaction();


    $info = \DB::table('funds_withdraw')
//                       ->where('status', 'pending')
        ->lockForUpdate()
        ->find($id);

    if (!$info) {
        $ci->db->getConnection()
            ->rollback();

        return $ci->lang->set(10560);
    }

    //同一个订单 不能同时操作 如一个人点拒绝 一个人点代付
    $withdrawOrder = $info->trade_no;
    if ($ci->redis->incr($withdrawOrder) > 1) {
        return $ci->lang->set(10517);
    }

    $ci->redis->expire($withdrawOrder, 4);

    $daifu = \DB::table('transfer_order')
        ->where('status', '=', 'pending')
        ->where('withdraw_order', '=', $info->trade_no)
        ->value('id');

    if ($daifu) {
        $ci->db->getConnection()
            ->rollback();

        return $ci->lang->set(10514);
    }

    $daifu = \DB::table('transfer_order')
        ->where('status', '=', 'paid')
        ->where('withdraw_order', '=', $info->trade_no)
        ->value('id');

    if ($daifu) {
        $ci->db->getConnection()
            ->rollback();

        return $ci->lang->set(10515);
    }

    $user = (new \Logic\User\User($ci))->getInfo($info->user_id);
    $user['id'] = $info->user_id;

    //判断状态
    if(($status == 'paid' && $info->status != 'obligation') || ($status == 'unlock' && $info->status != 'lock')){
        $ci->db->getConnection()
            ->rollback();
        return $ci->lang->set(10514);
    }

    if(($status == 'oblock' && $info->status != 'obligation') || ($status == 'obunlock' && $info->status != 'oblock')){
        $ci->db->getConnection()
            ->rollback();
        return $ci->lang->set(10514);
    }

    if(in_array($status,['rejected','confiscate','obligation','lock']) && (!in_array($info->status,['pending','obligation']))){
        $ci->db->getConnection()
            ->rollback();
        return $ci->lang->set(10514);
    }

    //修改状态
    if($status == 'unlock'){
        $update_status = 'pending';
    }elseif($status == 'obunlock'){
        $update_status = 'obligation';
    }else{
        $update_status = $status;
    }
    \DB::table('funds_withdraw')
        ->where('id', '=', $id)
        ->update([
            'status'       => $update_status,
            'process_time' => date('Y-m-d H:i:s'),
            'process_uid'  => 0,
            'memo'         => $comment,
        ]);

    $wallet = new \Logic\Wallet\Wallet($ci);

    //增加资金流水
    $funds =(array) \DB::table('funds')
        ->where('id', '=', $user['wallet_id'])
        ->get()
        ->first();
    //逻辑
    switch ($status) {
        //逻辑相同，rejected 审核失败  refused提现被拒
        case 'obligation':
        case 'lock':
        case 'unlock':
        case 'oblock':
        case 'obunlock':
            //无操作
            break;
        case 'rejected':
        case 'refused':
            $wallet->crease($user['wallet_id'], $info->money,$info->type);

            $title   = 'Withdrawal failed';
            $content = json_encode(["Dear user, your withdrawal of %s yuan on %s was rejected",$info->created, $info->money / 100]);
            if ($comment) {
                $content = json_encode(["Dear user, your withdrawal of %s yuan on %s was rejected, reason %s",$info->created, $info->money / 100, $comment]);
            }

            //股东分红钱包
            if($info->type == 2){
                $content = "Withdrawal failed share";
                $dealType=\Model\FundsDealLog::TYPE_WIRTDRAW_SHARE_REFUSE;
                $balance=$funds['share_balance'] + $info->money;
            }else{
                $dml = new \Logic\Wallet\Dml($ci);
                $dmlData = $dml->getUserDmlData($info->user_id, -$info->money, 3);

                \Model\UserData::where('user_id', '=', $info->user_id)
                    ->update(['free_money' => $dmlData->free_money]);
                $dealType=\Model\FundsDealLog::TYPE_WIRTDRAW_REFUSE;
                $balance=$funds['balance'] + $info->money;
            }

            //提现失败流水
            $dealData = [
                "user_id"           => $info->user_id,
                "username"          => $user['user_name'],
                "order_number"      => $info->trade_no,
                "deal_type"         => $dealType,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money"        => $info->money,
                "balance"           => $balance,
                "memo"              => $ci->lang->text('Withdrawal failed'),
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet ?? 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet ?? 0,
                'free_money'        => $dmlData->free_money ?? $balance,
                'admin_user'        => $admin_name,
                'admin_id'          => $admin_id,
            ];

            \Model\FundsDealLog::create($dealData);

            
            break;
        //提现审核通过进入到提现状态
        case 'paid':

            //股东分红钱包
            if($info->type == 2){
                $dealType=\Model\FundsDealLog::TYPE_SHARE_WITHDRAW;
                $balance=$funds['share_balance'];
            }else{
                $dealType=\Model\FundsDealLog::TYPE_WITHDRAW;
                $balance=$funds['balance'];
                //添加取款总笔数，总金额
                \Model\UserData::where('user_id',$info->user_id)->increment('withdraw_amount',$info->money,['withdraw_num'=>\DB::raw('withdraw_num + 1')]);
                //插入流水
                //流水里面添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($ci);
                $dmlData = $dml->getUserDmlData($info->user_id);
            }


            $dealData = [
                "user_id"           => $info->user_id,
                "username"          => $user['user_name'],
                "order_number"      => $info->trade_no,
                "deal_type"         => $dealType,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_COST,
                "deal_money"        => $info->money,
                "balance"           => $balance,
                "memo"              => $ci->lang->text('Withdrawal Successful'),
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet ?? 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet ?? 0,
                'free_money'        => $dmlData->free_money ?? $balance,
                'admin_user'        => $admin_name,
                'admin_id'          => $admin_id,
            ];

            \Model\FundsDealLog::create($dealData);
            $bank = json_decode($info->receive_bank_info);

            $ci->db->getConnection()
                ->commit();

            break;
        //提现审核进入到没收状态
        case 'confiscate':

            //股东分红钱包
            if($info->type == 2){
                $dealType=\Model\FundsDealLog::TYPE_SHARE_WITHDRAW_CONFISCATE;
                $balance=$funds['share_balance'];
            }else{
                $dealType=\Model\FundsDealLog::TYPE_WITHDRAW_CONFISCATE;
                $balance=$funds['balance'];
                //添加没收总笔数，总金额
                \Model\UserData::where('user_id',$info->user_id)->increment('confiscate_amount',$info->money,['confiscate_num'=>\DB::raw('confiscate_num + 1')]);

                //插入流水
                //流水里面添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($ci);
                $dmlData = $dml->getUserDmlData($info->user_id);
            }


            $dealData = [
                "user_id"           => $info->user_id,
                "username"          => $user['user_name'],
                "order_number"      => $info->trade_no,
                "deal_type"         => $dealType,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_COST,
                "deal_money"        => $info->money,
                "balance"           => $balance,
                "memo"              => $ci->lang->text('Withdrawal confiscated'),
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet ??0,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet ??0,
                'free_money'        => $dmlData->free_money ??$balance,
                'admin_user'        => $admin_name,
                'admin_id'          => $admin_id,
            ];

            \Model\FundsDealLog::create($dealData);
            
            

            $ci->db->getConnection()
                ->commit();

            break;
        default:
            return $ci->lang->set(-2);
    }

    $ci->db->getConnection()
        ->commit();


    $str="打款成功";
    if($status=='rejected'){
        $str="拒绝打款";
    }elseif($status=='obligation'){
        $str="审核通过";
    }elseif($status=='lock'){
        $str="锁定";
    }elseif($status=='unlock'){
        $str="解锁";
    }
    (new \Logic\Admin\Log($ci))->create($info->user_id, $user['user_name'], \Logic\Admin\Log::MODULE_CASH, '提现审核', '提现审核', $str, 1, "订单号：{$info->trade_no}");

    return $ci->lang->set(0);
} catch (\Exception $e) {
    $str="打款失败";
    $ci->db->getConnection()
        ->rollback();
    (new \Logic\Admin\Log($ci))->create($info->user_id, $user['user_name'], \Logic\Admin\Log::MODULE_CASH, '提现审核', '提现审核', $str, 0, "订单号：{$info->trade_no}");
    return $ci->lang->set(-2);
}
}
*/
