<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\FundsDealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '提现审核-没收';
    const DESCRIPTION = '提现审核-没收';

    const QUERY = [
        'id' => 'int(required) #提款申请id',
    ];

    const PARAMS = [
        'status'  => 'enum[rejected,paid](required)  #支付状态，rejected:拒绝, paid:支付',
        'comment' => 'string #拒绝理由',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run(){
        $ids = $this->request->getParam('ids');
        $status = $this->request->getParam('status');
        $comment = $this->request->getParam('comment');

        $validate = new \lib\validate\BaseValidate([
            'status' => 'in:confiscate',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        $id_arr = explode(',',$ids);
        if(empty($id_arr)){
            return $this->lang->set(10560);
        }
        if(count($id_arr) == 1){
            $this->checkID($id_arr[0]);
            return $this->item_run($id_arr[0], $status, $comment);
        }else{
            foreach($id_arr as $val){
                $this->checkID($val);
                $this->item_run($val, $status, $comment);
            }

            return $this->lang->set(0);
        }
    }

    public function item_run($id, $status, $comment) {
        $admin_id = $this->playLoad['uid'];
        $admin_name = $this->playLoad['nick'];
        try {
            $this->db->getConnection()
                ->beginTransaction();


            $info = \DB::table('funds_withdraw')
//                       ->where('status', 'pending')
                ->lockForUpdate()
                ->find($id);

            if (!$info) {
                $this->db->getConnection()
                    ->rollback();

                return $this->lang->set(10560);
            }

            //同一个订单 不能同时操作 如一个人点拒绝 一个人点代付
            $withdrawOrder = $info->trade_no;
            if ($this->redis->incr($withdrawOrder) > 1) {
                return $this->lang->set(10517);
            }

            $this->redis->expire($withdrawOrder, 4);

            $daifu = \DB::table('transfer_order')
                ->where('status', '=', 'pending')
                ->where('withdraw_order', '=', $info->trade_no)
                ->value('id');

            if ($daifu) {
                $this->db->getConnection()
                    ->rollback();

                return $this->lang->set(10514);
            }

            $daifu = \DB::table('transfer_order')
                ->where('status', '=', 'paid')
                ->where('withdraw_order', '=', $info->trade_no)
                ->value('id');

            if ($daifu) {
                $this->db->getConnection()
                    ->rollback();

                return $this->lang->set(10515);
            }

            $user = (new \Logic\User\User($this->ci))->getInfo($info->user_id);
            $user['id'] = $info->user_id;

            //判断状态
            if(($status == 'paid' && $info->status != 'obligation') || ($status == 'unlock' && $info->status != 'lock')){
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(10514);
            }

            if(($status == 'oblock' && $info->status != 'obligation') || ($status == 'obunlock' && $info->status != 'oblock')){
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(10514);
            }

            if(in_array($status,['rejected','confiscate','obligation','lock']) && (!in_array($info->status,['pending','obligation']))){
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(10514);
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
                    'process_uid'  => $this->playLoad['uid'],
                    'memo'         => $comment,
                ]);

            $wallet = new \Logic\Wallet\Wallet($this->ci);

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
                        $dealType=FundsDealLog::TYPE_WIRTDRAW_SHARE_REFUSE;
                        $balance=$funds['share_balance'] + $info->money;
                    }else{
                        $dml = new \Logic\Wallet\Dml($this->ci);
                        $dmlData = $dml->getUserDmlData($info->user_id, -$info->money, 3);

                        \Model\UserData::where('user_id', '=', $info->user_id)
                            ->update(['free_money' => $dmlData->free_money]);
                        $dealType=FundsDealLog::TYPE_WIRTDRAW_REFUSE;
                        $balance=$funds['balance'] + $info->money;
                    }

                    //提现失败流水
                    $dealData = [
                        "user_id"           => $info->user_id,
                        "username"          => $user['user_name'],
                        "order_number"      => $info->trade_no,
                        "deal_type"         => $dealType,
                        "deal_category"     => FundsDealLog::CATEGORY_INCOME,
                        "deal_money"        => $info->money,
                        "balance"           => $balance,
                        "memo"              => $this->lang->text('Withdrawal failed'),
                        "wallet_type"       => FundsDealLog::WALLET_TYPE_PRIMARY,
                        'total_bet'         => $dmlData->total_bet ?? 0,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet ?? 0,
                        'free_money'        => $dmlData->free_money ?? $balance,
                        'admin_user'        => $admin_name,
                        'admin_id'          => $admin_id,
                    ];

                    FundsDealLog::create($dealData);

                    //发送消息给客户
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange,[
                        'user_id'   => $info->user_id,
                        'user_name' => $user['user_name'],
                        'title'     => $title,
                        'content'   => $content,
                    ]);
                    break;
                //提现审核通过进入到提现状态
                case 'paid':

                    //股东分红钱包
                    if($info->type == 2){
                        $dealType=FundsDealLog::TYPE_SHARE_WITHDRAW;
                        $balance=$funds['share_balance'];
                    }else{
                        $dealType=FundsDealLog::TYPE_WITHDRAW;
                        $balance=$funds['balance'];
                        //添加取款总笔数，总金额
                        \Model\UserData::where('user_id',$info->user_id)->increment('withdraw_amount',$info->money,['withdraw_num'=>\DB::raw('withdraw_num + 1')]);
                        //插入流水
                        //流水里面添加打码量可提余额等信息
                        $dml = new \Logic\Wallet\Dml($this->ci);
                        $dmlData = $dml->getUserDmlData($info->user_id);
                    }


                    $dealData = [
                        "user_id"           => $info->user_id,
                        "username"          => $user['user_name'],
                        "order_number"      => $info->trade_no,
                        "deal_type"         => $dealType,
                        "deal_category"     => FundsDealLog::CATEGORY_COST,
                        "deal_money"        => $info->money,
                        "balance"           => $balance,
                        "memo"              => $this->lang->text('Withdrawal Successful'),
                        "wallet_type"       => FundsDealLog::WALLET_TYPE_PRIMARY,
                        'total_bet'         => $dmlData->total_bet ?? 0,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet ?? 0,
                        'free_money'        => $dmlData->free_money ?? $balance,
                        'admin_user'        => $admin_name,
                        'admin_id'          => $admin_id,
                    ];

                    FundsDealLog::create($dealData);
                    $bank = json_decode($info->receive_bank_info);

                    $title   = 'Withdrawal to account';
                    $content = ["Dear user, you have received %s yuan from %s on %s. Please check", $info->money / 100,$bank->bank,$info->created];
                    //发送消息给客户
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange,[
                        'user_id'   => $info->user_id,
                        'user_name' => $user['user_name'],
                        'title'     => $title,
                        'content'   => json_encode($content),
                    ]);


                    $this->db->getConnection()
                        ->commit();

                    break;
                //提现审核进入到没收状态
                case 'confiscate':

                    //股东分红钱包
                    if($info->type == 2){
                        $dealType=FundsDealLog::TYPE_SHARE_WITHDRAW_CONFISCATE;
                        $balance=$funds['share_balance'];
                    }else{
                        $dealType=FundsDealLog::TYPE_WITHDRAW_CONFISCATE;
                        $balance=$funds['balance'];
                        //添加没收总笔数，总金额
                        \Model\UserData::where('user_id',$info->user_id)->increment('confiscate_amount',$info->money,['confiscate_num'=>\DB::raw('confiscate_num + 1')]);

                        //插入流水
                        //流水里面添加打码量可提余额等信息
                        $dml = new \Logic\Wallet\Dml($this->ci);
                        $dmlData = $dml->getUserDmlData($info->user_id);
                    }


                    $dealData = [
                        "user_id"           => $info->user_id,
                        "username"          => $user['user_name'],
                        "order_number"      => $info->trade_no,
                        "deal_type"         => $dealType,
                        "deal_category"     => FundsDealLog::CATEGORY_COST,
                        "deal_money"        => $info->money,
                        "balance"           => $balance,
                        "memo"              => $this->lang->text('Withdrawal confiscated'),
                        "wallet_type"       => FundsDealLog::WALLET_TYPE_PRIMARY,
                        'total_bet'         => $dmlData->total_bet ??0,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet ??0,
                        'free_money'        => $dmlData->free_money ??$balance,
                        'admin_user'        => $admin_name,
                        'admin_id'          => $admin_id,
                    ];

                    FundsDealLog::create($dealData);
                    $bank = json_decode($info->receive_bank_info);

                    $title   = $this->lang->text('Withdrawal confiscated title');
                    $content = $this->lang->text('Withdrawal confiscated content');
                    //发送消息给客户
                    //发送回水信息
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange,[
                        'user_id'   => $info->user_id,
                        'user_name' => $user['user_name'],
                        'title'     => $title,
                        'content'   => $content,
                    ]);

                    $this->db->getConnection()
                        ->commit();

                    break;
                default:
                    return $this->lang->set(-2);
            }

            $this->db->getConnection()
                ->commit();

            /*============================日志操作代码================================*/

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
            (new Log($this->ci))->create($info->user_id, $user['user_name'], Log::MODULE_CASH, '提现审核', '提现审核', $str, 1, "订单号：{$info->trade_no}");
            /*============================================================*/

            return $this->lang->set(0);
        } catch (\Exception $e) {
            $str="打款失败";
            $this->db->getConnection()
                ->rollback();
            (new Log($this->ci))->create($info->user_id, $user['user_name'], Log::MODULE_CASH, '提现审核', '提现审核', $str, 0, "订单号：{$info->trade_no}");
            return $this->lang->set(-2);
        }
    }
};
