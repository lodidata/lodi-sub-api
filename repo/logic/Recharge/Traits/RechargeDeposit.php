<?php
namespace Logic\Recharge\Traits;

use Logic\Set\SystemConfig;
use Logic\User\User;
use Model\FundsDeposit;
use Logic\Admin\Message;
use Logic\Activity\Activity;
use Logic\Wallet\Wallet;
use Model\FundsDealLog;
use lib\exception\BaseException;
trait RechargeDeposit{
    /**
     * 更改线下存款信息
     *
     * @param int   $id 存款ID <br />
     * @param array $offline <br />
     * send_coupon：是否发放优惠，1: 是, 0: 否 <br />
     * send_memo：是否发送备注，1: 是, 0: 否<br />
     * memo：备注<br />
     * process_uid：处理人<br />
     * status：状态，1: 通过， 2: 拒绝<br />
     * @return bool
     */
    public function updateOffline($id, $offline)
    {

        if (isset($offline['status'])) {
            // 无效存款无法通过
            $deposit    = FundsDeposit::where('status','pending')->find($id);//这里查询需要加where status = pending,防止通过或拒绝的订单再次修改

            if (!$deposit) {
                $newResponse = createRsponse($this->response,200,10550,$this->lang->text("Invalid deposit or processed!"));
                throw new BaseException($this->request,$newResponse);
            }

            $user       = (new \Logic\User\User($this->ci))->getInfo($deposit->user_id);
            $userName   = $user['user_name'];
            $message = new Message($this->ci);
            // 通过
            if ($offline['status'] == 1) {

                $send_coupon = $offline['send_coupon'] == 1;
                //判断是否禁止优惠 和 禁止取款优惠
                $auth_status = \DB::table('user')->where('id',$deposit->user_id)->value('auth_status');

                if(strpos($auth_status,'refuse_sale')){
                    $send_coupon = false;
                }

                //通过
                $rs = $this->passDeposit($offline['process_uid'], $send_coupon, $offline['memo'] ?? '',$offline['send_memo'], $deposit);//存入

                if($rs){
                    $content  = ["Dear %s ! Your recharge of %s yuan on %s has arrived",$userName,$rs['money'],$deposit->created];
                    $insertId = $this->messageAddByMan("Recharge to account",$userName,$content);
                    $message->messagePublish($insertId);

                    $active_apply = !empty($deposit->active_apply) ? $deposit->active_apply : '';
                    $actives      = explode(',',$active_apply);
                    if($send_coupon && $rs['coupon'] > 0) { 
                        
                        // 查找maya渠道下所有通道
                        $channel = \DB::table('pay_channel')->where('type', 'qr')->first('id');
                        $ids = \DB::table('payment_channel')->where('pay_channel_id', $channel->id)->pluck('id');
                        FundsDeposit::where('user_id',$deposit->user_id)
                                    ->where('money','>',0)
                                    ->whereNotIn('payment_id', $ids)
                                    ->where('status','pending')->update(['active_apply'=>'','coupon_money'=>0]);

                        $content = ["Dear %s ! You participated in the recharge activities on %s, and %s yuan as a gift has arrived",$userName, $deposit->created, $rs['coupon']];
                        $insertId = $this->messageAddByMan("Recharge gift", $userName, $content);
                        $message->messagePublish($insertId);
                    }else{
                        if(count($actives))
                            \DB::table('active_apply')
                                ->where('trade_id',$id)
                                ->where('user_id',$deposit->user_id)
                                ->whereIn('active_id',$actives)
                                ->update(['status'=>'rejected']);
                    }
                    //发放直推-充值奖励
                    $obj = new \Logic\Recharge\Recharge($this->ci);
                    $obj->directRechargeAward($deposit->user_id,$deposit->money);

                }

            } elseif ($offline['status'] == 2) {
                // 拒绝
                $rs = $this->refuseDeposit($offline['process_uid'], $offline['memo'] ?? '', $offline['send_memo'],$deposit);
                $reson = $offline['memo'];
                $deposit->money = $deposit->money /100;
                $content = ["Dear user, your recharge of %s yuan on %s was rejected. If you have any questions, please contact online customer service", $deposit->money, $deposit->created];
                if($reson){
                    $content = ["Dear user, you were refused to recharge %s yuan on %s. Reason for rejection: %s. If you have any questions, please contact online customer service", $deposit->money, $deposit->created, $reson];
                }
                $insertId = $this->messageAddByMan("Recharge failed", $userName, $content);
                $message->messagePublish($insertId);
            }
            return $rs;
        }
    }

    /**
     * 通过线下入款单
     *
     * @param int    $currentUserId 当前用户id
     * @param bool   $sendCoupon 是否发送优惠
     * @param string $memo 备注
     * @param bool   $sendMemo 是否发送备注
     * @param array/object    $deposit 入款单详情
     * @return bool
     */
    public function passDeposit(
        $currentUserId,
        $sendCoupon = true,
        $memo = '',
        $sendMemo = false,
        $deposit
    ) {
        if ($deposit->status != 'pending') {
            return false;
        }
        $valid_bet      = 0;
        $state  = $sendCoupon ? '|16' : '';
        $state .= $sendMemo ? '|32' : '';
        $state = "state{$state}";
        $model          = [
            'valid_bet'     => $valid_bet,
            'process_time'  => date('Y-m-d H:i:s'),
            'recharge_time' => date('Y-m-d H:i:s'),
            'process_uid'   => $currentUserId,
            'updated_uid'   => $currentUserId,
            'status'        => 'paid',
            'state'         => \DB::raw($state),
            'memo'          => $memo ?? '',
        ];
        $model['marks'] = 0;
        // 将充值金额转入钱包
//        $deposit->coupon_money = $sendCoupon ? intval($deposit->coupon_money) : 0;
//        $amount = $deposit->money + $deposit->coupon_money;// 充值金额=本金+优惠
        $user  = (new \Logic\User\User($this->ci))->getInfo($deposit->user_id);
        $re    = $this->rechargeMoney($model,$deposit,$user,$deposit->money,false, $sendCoupon);
        return $re;
    }

    /**
     * 拒绝线下入款单
     *
     * @param int    $currentUserId 当前用户id
     * @param string $memo 备注
     * @param bool   $sendMemo 是否发送备注
     * @param object   $deposit 是否发送备注
     * @return bool
     */
    public function refuseDeposit($currentUserId, $memo = '', $sendMemo = false,$deposit)
    {
        try {
            $this->db->getConnection()->beginTransaction();
            $deposit = \Model\FundsDeposit::where('id', $deposit->id)->lockForUpdate()->first();
            if ($deposit->status != 'pending') {
                $this->db->getConnection()->rollback();
                throw new \Exception($this->lang->text("Order approved") . ":".$deposit->status);
            }
            $state = $sendMemo ? '|32' : '';
            $state = "state{$state}";
            $model          = [
                'process_time'  => date('Y-m-d H:i:s'),
                'recharge_time' => date('Y-m-d H:i:s'),
                'process_uid'   => $currentUserId,
                'updated_uid'   => $currentUserId,
                'status'        => 'rejected',
                'state'     => \DB::raw($state),
                'memo'          => $memo
            ];
            $model['marks'] = 0;
            \DB::table('funds_deposit')->where('id',$deposit->id)->update($model);
            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return false;
        }
        return true;
    }




    /**
     * 充值到账，操作钱包
     *
     * @param array    $model 充值更新数据
     * @param object    $deposit    当前充值信息
     * @param array    $user  用户信息
     * @param int  $amount  充值金额+优惠金额
     * @return bool
     */
    public function rechargeMoney($model,$deposit,$user,$amount,$online = true,$sendCoupon = true){
        try {
            $this->db->getConnection()->beginTransaction();

            $deposit = \Model\FundsDeposit::where('id', $deposit->id)->lockForUpdate()->first();

            if ($deposit->status != 'pending') {
                $this->db->getConnection()->rollback();
                throw new \Exception($this->lang->text("Order approved") . ":".$deposit->status);
            }
            //充值赠送
            $rechargeGive=$this->rechargeGive($deposit);

            //充值时剩余金额小于10
            $money = \DB::table('funds')->where('id','=',$user['wallet_id'])->value('balance');
            $this->setCanPlayAllGame($deposit->user_id, $money, $user['wallet_id']);


            $dmlMoney=bcadd($money,$deposit->money);
            $this->userDml($deposit->user_id,$dmlMoney);

            $deposit->coupon_money = 0;
            $pay_way = '';

            // app充值活动特殊处理
            $active         = new Activity($this->ci);
            $appTopUpIdActive = $active->getAppTopUpGiftStauts();
            //更新该用户当天其他未支付充值订单的活动金额和ID  以防实际支付金额与订单金额有出入，所以重新计算对应的
            if($sendCoupon && ($deposit->active_apply || $appTopUpIdActive)){
                $user['id']     = $deposit->user_id;
                $pay_way        = $online ? ' |8' : '|128';
                $pay_way2       = $online ? 'online' : 'offline';
                $tmp            = explode(',',$deposit->active_apply);
                $needActiveIds  = '';
                if($tmp) {
                    $needActiveIds  = implode(',',\DB::table('active_apply')->whereIn('id',$tmp)->pluck('active_id')->toArray());
                }
                if($appTopUpIdActive && in_array($deposit->origin, [3, 4])){
                    $needActiveIds = !empty($needActiveIds) ? ',' . $appTopUpIdActive->id : $appTopUpIdActive->id;
                }

                $activeData     = $active->rechargeActive($deposit->user_id, $user, $deposit->money, $needActiveIds, $pay_way2, $deposit->id);
                $deposit->active_apply        = $model['active_apply'] = $activeData['activeApply'];
                $deposit->coupon_money        = $model['coupon_money'] = $activeData['coupon_money'] ?? 0;
                $deposit->withdraw_bet        = $model['withdraw_bet'] = $activeData['withdraw_bet'] ?? 0;
                $deposit->coupon_withdraw_bet = $model['coupon_withdraw_bet'] = $activeData['coupon_withdraw_bet'] ?? 0;

               /* if (isset($activeData['state']) && $activeData['state']){
                    $pay_way = $pay_way . ' |2';
                    $model['state'] = \DB::raw("state{$pay_way}");
                }*/

                $this->updateDepositActives($deposit,$activeData['state'],$activeData['today_state'],$activeData['slot_coupon'],$activeData['rechargeActive'],$activeData['maya_state']);
            }


            // 支付渠道赠送
            if ($rechargeGive['money'] > 0) {
                $deposit->coupon_money = bcadd($deposit->coupon_money, $rechargeGive['money']);
            }

            // 是否首存
            $isNew = FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money','>',0)->where('user_id', '=', $deposit->user_id)->first();
            if (!$isNew) {
                $pay_way = $pay_way . ' |2';
                $model['state'] = \DB::raw("state{$pay_way}");
            }

            //对应充值通道活动优惠
            //赠送活动规则switch（1开，0关）type（1首次，0不限即每次）
            //{"switch":"1","type":"1","","recharge":"10000","send":20","max_send":"50000","send_dml":"200"}
            $pass_active_rule       = json_decode($deposit->passageway_active,true);
            $pass_active_money      = 0;
            $pass_active_money_dml  = 0;

            if(is_array($pass_active_rule) && $pass_active_rule['switch'] == 1) {
                switch ($pass_active_rule['type']) {
                    case 1:
                        $first = FundsDeposit::where('user_id',$deposit->user_id)
                            ->where('status','paid')
                            ->where('money','>',0)
                            ->where('receive_bank_account_id',$deposit->receive_bank_account_id)
                            ->where('created','>=',date('Y-m-d'))
                            ->where('created','<=',date('Y-m-d 23:59:59'))->value('id');
                        if(!$first && $deposit->money >= $pass_active_rule['recharge']) {
                            $pass_active_money = $deposit->money * $pass_active_rule['send']/100;
                        }
                        break;
                    case 0:
                        if($deposit->money >= $pass_active_rule['recharge']) {
                            $pass_active_money = $deposit->money * $pass_active_rule['send']/100;
                        }
                        break;
                }
                $pass_active_money              = $pass_active_money > $pass_active_rule['max_send'] ? $pass_active_rule['max_send'] : $pass_active_money ;
                $pass_active_rule['send_dml']   = isset($pass_active_rule['send_dml']) ? $pass_active_rule['send_dml'] : 0;
                $pass_active_money_dml          = $pass_active_money * $pass_active_rule['send_dml'] / 100;
            }


            // 锁定钱包
            \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            $amount = $amount + $deposit->coupon_money + $pass_active_money;
            (new Wallet($this->ci))->crease($user['wallet_id'], $amount);

            // 修改入款单状态
            \DB::table('funds_deposit')->where('id',$deposit->id)->update($model);
            $funGateway = array(
                'pay_no'        => $deposit->pay_no,
                'status'        => 'finished',
                'inner_status'  => 'finished',
                'notify_time'   => date('Y-m-d H:i:s')
        );
            \DB::table('funds_gateway')->where('pay_no','=',$deposit->pay_no)->update($funGateway);

            $this->db->getConnection()->commit();


            $funds=\DB::table('funds as f')
                        ->leftJoin('funds_child as fc','f.id','=','fc.pid')
                        ->selectRaw('sum(fc.balance) + f.balance as balance')
                        ->where('f.id',$user['wallet_id'])
                        ->first();
            $this->userDml($deposit->user_id,$funds->balance);

            //TODO 分离事务 2022-03-25

            //添加打码量记录
            $dmllog = new \Model\Dml();
            $dmllog->addDml($deposit->user_id, $deposit->withdraw_bet + $rechargeGive['rechargeDml'], $deposit->money, $this->lang->text("Recharge to add code amount"));
            //添加打码量可提余额等信息  打码量信息必须 在  增加金额之前
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData((int)$deposit->user_id, (int)$deposit->withdraw_bet + $rechargeGive['rechargeDml'], 2);
            $money = \DB::table('funds')->where('id', '=', $user['wallet_id'])->value('balance');

            //修改用户  首充时间
            \Model\User::where('id', $deposit->user_id)->whereRaw('first_recharge_time is NULL')
                ->update(['first_recharge_time' => $model['recharge_time']]);

            //添加存款总笔数，总金额
            \Model\UserData::where('user_id',$deposit->user_id)->increment('deposit_amount',$deposit->money,['deposit_num'=>\DB::raw('deposit_num + 1')]);
            if(isset($GLOBALS['playLoad'])) {
                $admin_id   = $GLOBALS['playLoad']['uid'];
                $admin_name = $GLOBALS['playLoad']['nick'];
            }else {
                $admin_id   = 0;
                $admin_name = '';
            }

            // 增加资金流水
            $dealData = array(
                "user_id"           => $deposit->user_id,
                "username"          => $user['user_name'],
                "order_number"      => $deposit->trade_no,
                "deal_type"         => $online ? FundsDealLog::TYPE_INCOME_ONLINE : FundsDealLog::TYPE_INCOME_OFFLINE,
                "deal_category"     => FundsDealLog::CATEGORY_INCOME,
                "deal_money"        => $deposit->money,
                "balance"           => $money - $deposit->coupon_money,   //该条交易流水  操作后余额应是在优惠金额之后
                "memo"              => $online ? $this->lang->text("Online recharge") : $this->lang->text("Offline recharge"),
                "wallet_type"       => 1,
                'total_bet'         =>$dmlData->total_bet,
                'withdraw_bet'      => $deposit->withdraw_bet + $rechargeGive['rechargeDml'],
                'total_require_bet' =>$dmlData->total_require_bet,
                'free_money'        =>$dmlData->free_money,
                'admin_user'        =>$admin_name,
                'admin_id'          =>$admin_id,
            );

            $dealLogId = FundsDealLog::create($dealData)->id;

            if ($deposit->coupon_money > 0 && $sendCoupon && $deposit->active_apply) {
                $dmllog->addDml($deposit->user_id,$deposit->coupon_withdraw_bet,$deposit->coupon_money,$this->lang->text("Add code amount for free in recharge activities"));
                $dmlData =$dml->getUserDmlData((int)$deposit->user_id,(int)$deposit->coupon_withdraw_bet,2);
                $dealData['deal_type']          = FundsDealLog::TYPE_ACTIVITY;
                $dealData['deal_category']      = FundsDealLog::CATEGORY_INCOME;
                $dealData['balance']            = $money - $rechargeGive['money'];
                $dealData['deal_money']         = $deposit->coupon_money;
                $dealData['withdraw_bet']       = $deposit->coupon_withdraw_bet;
                $dealData['total_require_bet']  = $dmlData->total_require_bet;
                $dealData['free_money']         = $dmlData->free_money;
                $dealData['total_bet']          = $dmlData->total_bet;
                $dealData['admin_user']         = $admin_name;
                $dealData['admin_id']           = $admin_id;
                $dealData['memo']               = $this->lang->text("Gift of recharge activity");

                FundsDealLog::create($dealData);
            }

            if ($pass_active_money > 0) {
                if($pass_active_money_dml) {
                    $dmllog->addDml($deposit->user_id, $pass_active_money_dml, $deposit->money, $this->lang->text("Add code amount to the free amount of recharge channel"));
                    $dmlData =$dml->getUserDmlData((int)$deposit->user_id,(int)$pass_active_money_dml,2);
                }
                $dealData['deal_type']          = FundsDealLog::TYPE_ACTIVITY;
                $dealData['deal_category']      = FundsDealLog::CATEGORY_INCOME;
                $dealData['balance']            = $money;
                $dealData['deal_money']         = $pass_active_money;
                $dealData['withdraw_bet']       = $pass_active_money_dml;
                $dealData['total_require_bet']  = $dmlData->total_require_bet;
                $dealData['free_money']         = $dmlData->free_money;
                $dealData['total_bet']          = $dmlData->total_bet;
                $dealData['admin_user']         = $admin_name;
                $dealData['admin_id']           = $admin_id;
                $dealData['memo']               = $this->lang->text("Channel recharge activity gift").$pass_active_rule['send'].'%';
                FundsDealLog::create($dealData);
            }
            if($rechargeGive['money'] >0  || $rechargeGive['lotteryDml'] > 0){
                if($rechargeGive['lotteryDml']) {
                    $dmllog->addDml($deposit->user_id,  $rechargeGive['lotteryDml'], $deposit->money, $this->lang->text("Add code amount to the free amount of recharge channel"));
                    $dmlData =$dml->getUserDmlData((int)$deposit->user_id,(int)$rechargeGive['lotteryDml'],2);
                }
                $dealData['deal_type']          = FundsDealLog::TYPE_ACTIVITY;
                $dealData['deal_category']      = FundsDealLog::CATEGORY_INCOME;
                $dealData['balance']            = $money;
                $dealData['deal_money']         = $rechargeGive['money'];
                $dealData['withdraw_bet']       = $rechargeGive['lotteryDml'];
                $dealData['total_require_bet']  = $dmlData->total_require_bet;
                $dealData['free_money']         = $dmlData->free_money;
                $dealData['total_bet']          = $dmlData->total_bet;
                $dealData['admin_user']         = $admin_name;
                $dealData['admin_id']           = $admin_id;
                $dealData['memo']               = 'payment_give ' . $this->lang->text("Channel recharge activity gift").$rechargeGive['money'] /100;
                FundsDealLog::create($dealData);
            }
            // 修改入款单状态
            $model['coupon_money'] = $deposit->coupon_money;
            \DB::table('funds_deposit')->where('id',$deposit->id)->update($model);
            //更改其他订单优惠
            $date = date('Y-m-d');

            // 查找maya渠道下所有通道
            $ids = [];
            $channel = \DB::table('pay_channel')->where('type', 'qr')->first('id');
            if (!empty($channel)) {
                $ids = \DB::table('payment_channel')->where('pay_channel_id', $channel->id)->pluck('id');
            }
            \DB::table('funds_deposit')
                ->where('user_id',$deposit->user_id)
                ->whereRaw("status != 'paid'")
                ->whereRaw("created >='$date'")
                ->whereRaw("created <= '$date 23:59:59'")
                ->when(!empty($ids), function ($query) use($ids) {
                    $query->whereNotIn('payment_id', $ids);
                })
                ->update(['coupon_money'=>0,'active_apply'=>'']);
            $funGateway = array(
                'pay_no'        => $deposit->pay_no,
                'status'        => 'finished',
                'inner_status'  => 'finished',
                'notify_time'   => date('Y-m-d H:i:s')
            );
            \DB::table('funds_gateway')->where('pay_no','=',$deposit->pay_no)->update($funGateway);

            $resData = [
                'log_id'    => $dealLogId,
                'user_id'   => $deposit->user_id,
                'name'      => $user['user_name'],
                'money'     => $deposit->money / 100,
                'coupon'    => $deposit->coupon_money / 100
            ];
            //赠送转卡彩金
            if(!$online){
                $user['id'] = $deposit->user_id;
                (new User($this->ci))->sendTransferHandsel($user,$deposit->trade_no,$deposit->money);
            }

            //幸运轮盘充值赠送免费抽奖次数
            if($resData){
                $wallet = new \Logic\Wallet\Wallet($this->ci);
                $wallet->luckycode($deposit->user_id);
            }

            //用户层级分层
            \Utils\MQServer::send('user_level_upgrade', ['user_id' => $deposit->user_id]);
            //第三方统计发送消息
            $this->thirdEventMsg($deposit);

            return $resData;
        }catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
            return false;
        }
    }

    public function thirdEventMsg($deposit){
        //af消息
        $mqMsg=[
            'user_id'=>$deposit->user_id,
            'eventName'=>'deposit',
            'amount'=>$deposit->money,
            'app_id'=>'',
            'dev_key'=>'',
            'appsflyer_id'=>'',
        ];
        $urdpData=\DB::table('user_register_deposit_log')
            ->where('user_id','=',$deposit->user_id)
            ->first();
        if (!empty($urdpData->app_id) && !empty($urdpData->dev_key) && !empty($urdpData->appsflyer_id)){
            $mqMsg['app_id']=$urdpData->app_id;
            $mqMsg['dev_key']=$urdpData->dev_key;
            $mqMsg['appsflyer_id']=$urdpData->appsflyer_id;
            \Utils\MQServer::send('user_statistics_req_dep',$mqMsg);
        }

        //firebase消息
        $user_firebase =\DB::table('user_firebase')
            ->where('user_id', $deposit->user_id)
            ->first();
        $firebaseMsg = [];
        if (!empty($user_firebase->firebase_app_id) && !empty($user_firebase->api_secret) && !empty($user_firebase->app_instance_id)){
            $firebaseMsg = [
                'user_id'           => $deposit->user_id,
                'eventName'         => 'deposit',
                'amount'            => bcdiv($deposit->money,100),
                'firebase_app_id'   => $user_firebase->firebase_app_id,
                'api_secret'        => $user_firebase->api_secret,
                'app_instance_id'   => $user_firebase->app_instance_id,
                'fire_user_id'      => $user_firebase->fire_user_id??'',
            ];
            \Utils\MQServer::send('user_statistics_req_dep',$firebaseMsg);
        }

        //adjust 消息
        $user_adjust =\DB::table('user_adjust')
            ->where('user_id', $deposit->user_id)
            ->first();
        $adjustMsg = [];
        if (!empty($user_adjust->app_token) && !empty($user_adjust->api_token) && !empty($user_adjust->event_token_json)){
            $adjustMsg = [
                'user_id'           => $deposit->user_id,
                'eventName'         => 'deposit',
                'amount'            => bcdiv($deposit->money,100),
                'app_token'         => $user_adjust->app_token,
                'api_token'         => $user_adjust->api_token,
                'event_token_json'  => $user_adjust->event_token_json,

            ];

            !empty($user_adjust->idfa) && $adjustMsg['idfa']              = $user_adjust->idfa;
            !empty($user_adjust->gps_adid) && $adjustMsg['gps_adid']      = $user_adjust->gps_adid;
            !empty($user_adjust->adid) && $adjustMsg['adid']              = $user_adjust->adid;
            !empty($user_adjust->fire_adid) && $adjustMsg['fire_adid']    = $user_adjust->fire_adid;
            !empty($user_adjust->oaid) && $adjustMsg['oaid']              = $user_adjust->oaid;

            \Utils\MQServer::send('user_statistics_req_dep',$adjustMsg);
        }
    }

    public function updateDepositActives($deposit,$first_deposited,$today_first_deposited,$slotCoupon=null,$rechargeActive,$mayaFirstDeposited){

        if (empty($deposit->active_apply))
            return ;

        foreach(explode(',',$deposit->active_apply) ?? [] as $active_id){

            $active = \DB::table('active_apply as apply')
                ->leftJoin('active as a','apply.active_id','=','a.id')
                ->selectRaw('apply.`user_id`,apply.`active_id`,apply.`state`,a.type_id,apply.coupon_money')
                ->where('apply.id',$active_id)
                ->first();
            $active = (array) $active;
            //新人首充加钱
            if(isset($active['type_id']) && $active['type_id'] == 2 && $first_deposited){

                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pending']);
                }
            }
            //每日首充加钱
            if(isset($active['type_id']) && $active['type_id'] == 3 && $today_first_deposited){

                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pending']);
                }
            }
            //电子优惠
            if(isset($active['type_id']) && $active['type_id'] == 7 && $slotCoupon){

                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                    //更新电子优惠参与记录
                    Activity::updateActiveSignUp($deposit->user_id, $active['active_id'], $deposit->created);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id', $active_id)->update(['status'=>'pending']);
                }
            }
            //充值活动
            if(isset($active['type_id']) && $active['type_id'] == 11 && $rechargeActive){

                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id', $active_id)->update(['status'=>'pending']);
                }
            }
            //maya首充加钱
            if(isset($active['type_id']) && $active['type_id'] == 17 && $mayaFirstDeposited){

                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pending']);
                }
            }
            // app充值
            if(isset($active['type_id']) && $active['type_id'] == 18){
                if($active['state'] == 'auto'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pass']);
                }else if($active['state'] == 'manual'){
                    \DB::table('active_apply')->where('id',$active_id)->update(['status'=>'pending']);
                }
            }
        }
    }

    /**
     * 参与充值优惠时 总金额小于10元 再次充值就能触发 可以玩所有游戏
     * @param $userId
     * @param $money
     * @param $walletId
     * @throws \Exception
     */
    public function setCanPlayAllGame($userId, $money, $walletId){
        if($money < 1000){
            // 如果用户不能玩所有游戏
            if(!Activity::canPlayAllGame($userId)){
                // 回收所有第三方
                \Logic\GameApi\GameApi::rollOutAllThird($userId);
                $new_money = \DB::table('funds')->where('id','=',$walletId)->value('balance');
                if($new_money > 1000){
                    return false;
                }
                $list      = \Model\FundsChild::select('balance')->where('pid', $walletId)->where('status','enabled')->get()->toArray();

                $total_third_money = 0; //第三方总金额

                foreach ($list as $val) {
                    // 总金额累计
                    $total_third_money += $val['balance'];
                }

                //总金额小于10元 才修改
                if($total_third_money + $new_money < 1000){
                    //就修改 (其实如果这次有电子优惠，就不用修改，因为懒得判断，就下面优惠里再改回去)
                    Activity::canPlayAllGame($userId, true);
                }

            }

        }
    }

    public function rechargeGive($deposit){
        $money=0;
        $rechargeDml=0;
        $lotteryDml=0;
        if(strpos($deposit->state,'offline') || $deposit->state == 'offline'){
            //线下
            $payConfig=\DB::table('pay_channel')->where('type','=','localbank')->first();
        }else if(strpos($deposit->state,'online') || $deposit->state == 'online'){
            //线上
            $payConfig=\DB::table('payment_channel')->where('id',$deposit->payment_id)->first();
        }


        if(!empty($payConfig)){
            if($payConfig->give ==1){
                $money=bcmul($deposit->money,bcdiv($payConfig->give_protion,100,4),2);
            }
            //充值赠送打码量
            $rechargeDml=bcmul($deposit->money,$payConfig->give_recharge_dml,2);
            //彩金赠送打码量
            $lotteryDml=bcmul($money,$payConfig->give_lottery_dml);
        }
        return compact('money','rechargeDml','lotteryDml');
    }

    //清空打码量
    public function userDml($userId,$money){
        $dmlAmount=SystemConfig::getModuleSystemConfig('system')['dml_amount'];
        if(isset($dmlAmount) && !empty($dmlAmount)){
            if($money <= $dmlAmount){
                //清空实际打码量和应有打码量
                \DB::table('user_data')->where('user_id',$userId)->update(['total_bet'=>0,'total_require_bet'=>0]);
            }
        }
    }

}