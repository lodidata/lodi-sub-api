<?php
namespace Logic\Lottery;

use \Logic\Wallet\Wallet;
use Model\LotteryChaseOrder;
use Model\LotteryChaseOrderSub;
use Model\LotteryInfo;
use \Model\LotteryOrder;
use \Model\LotteryOrderTemp;
use \Model\LotteryChase;
use Model\LotteryTrialChaseOrder;
use Model\User;

/**
 * 追号模块
 */
class ChaseOrder extends \Logic\Logic {

    /**
     * 追号消息通知入口
     * @param  [type] $lotteryId     [description]
     * @param  [type] $lotteryNumber [description]
     * @return [type]                [description]
     */
    public function runByNotify($lotteryId, $lotteryNumber) {
        //underway表示追号进行中的订单
        $list = LotteryChaseOrder::where('lottery_id',$lotteryId)->where('state','=','underway')->get(['chase_number','sum_periods'])->toArray();
        if (!$list) {
            $this->logger->debug("追号数据为空: $lotteryId, $lotteryNumber");
            return false;
        }
        $this->logger->debug("追号通知 {$lotteryId} {$lotteryNumber}");

//            $list = array_chunk($list,180);
        foreach ($list as $v){
            $queryChase = \DB::table('lottery_chase_order_sub')->where('chase_number',$v['chase_number'])
                ->where('lottery_number',$lotteryNumber)->where('state','=','default');//default 未生成订单
            $chase = $queryChase->first();
            if($chase) {//就否有当期未生成的订单，有则插入追号临时表
                $chase = (array)$chase;
                try {
//                    $this->db->getConnection()->beginTransaction();
                    $queryChase->update(['order_number' => '', 'state' => 'created']);
                    $chase['state'] = 'created';
                    \DB::table('lottery_chase_order_sub_temp')->insertGetId($chase);
                    LotteryChaseOrder::where('chase_number', $v['chase_number'])->update(['complete_periods' => \DB::raw('complete_periods + 1')]);
//                    $this->db->getConnection()->commit();
                } catch (\Exception $e) {
//                    $this->db->getConnection()->rollback();
                    //@todo 出错??
                    $this->logger->debug('Chase Error:' . $e->getMessage());
                    return $e->getMessage();
                }
            }
        }
    }

    /**
     * 试玩追号消息通知入口
     * @param  [type] $lotteryId     [description]
     * @param  [type] $lotteryNumber [description]
     * @return [type]                [description]
     */
    public function runTrialByNotify($lotteryId, $lotteryNumber) {
        $list = LotteryTrialChaseOrder::where('lottery_id',$lotteryId)->where('state','=','underway')->get(['chase_number','sum_periods'])->toArray();
        if (!$list) {
            $this->logger->debug("追号数据为空: $lotteryId, $lotteryNumber");
            return false;
        }
        $this->logger->debug("追号通知 {$lotteryId} {$lotteryNumber}");
            foreach ($list as $v){
                \DB::beginTransaction();
                try {
                    $queryChase = \DB::table('lottery_trial_chase_order_sub')->where('chase_number', $v['chase_number'])
                        ->where('lottery_number', $lotteryNumber)->where('state', '=', 'default');
                    $chase = $queryChase->first();
                    if ($chase) {       //就否有当期未生成的订单，有则插入追号临时表
                        $chase = (array)$chase;
                        $queryChase->update(['order_number' => '', 'state' => 'created']);
                        $chase['state'] = 'created';
                        \DB::table('lottery_trial_chase_order_sub_temp')->insertGetId($chase);
                        LotteryTrialChaseOrder::where('chase_number', $v['chase_number'])->update(['complete_periods' => \DB::raw('complete_periods + 1')]);
                    }
                    \DB::commit();
                }catch (\Exception $e){
                    $this->logger->debug('Chase Error:' . $e->getMessage());
                    \DB::rollback();
                }
            }
    }
    /**
     * 补追号订单生成失败问题
     * @return [type] [description]
     */
    public function chaseReCreate2() {
        //补追号只补当天的  定时五到十分钟跑一次当天生成失败的
        $stime = date('Y-m-d H:i:s',strtotime("-7 day"));
        $etime = date('Y-m-d H:i:s',time() - 5*60);
        $sql = "SELECT a.* FROM 
                (SELECT * FROM lottery_chase_order_sub 
                WHERE state = 'created' AND created > '{$stime}' AND created < '{$etime}') a
                LEFT JOIN lottery_chase_order b ON a.chase_number = b.chase_number 
                LEFT JOIN lottery_info c ON a.lottery_number = c.lottery_number AND b.lottery_id= c.lottery_type
                WHERE c.period_code != '' ";
        $data = \DB::select($sql);
        if($data){
            foreach ($data as &$v){
                $v = (array)$v;
                try {
                    \DB::table('lottery_chase_order_sub_temp')->insert($v);
                }catch (\Exception $e) {
                }
            }
        }
    }
    /**
     * 补追号订单生成失败问题
     * @return [type] [description]
     */
    public function chaseReCreate() {
        //补追号只补当天的  定时五到十分钟跑一次当天生成失败的
        $stime = date('Y-m-d H:i:s',strtotime("-7 day"));
        $etime = date('Y-m-d H:i:s',time() - 60);
        $sql = "SELECT a.* FROM 
                (SELECT * FROM lottery_chase_order_sub 
                WHERE state = 'default' AND created > '{$stime}' AND created < '{$etime}') a
                LEFT JOIN lottery_chase_order b ON a.chase_number = b.chase_number 
                LEFT JOIN lottery_info c ON a.lottery_number = c.lottery_number AND b.lottery_id= c.lottery_type
                WHERE c.period_code != '' ";
        $data = \DB::select($sql);
        if($data){
            $ids = [];
            foreach ($data as &$v){
                $v = (array)$v;
                try {
                    $v['state'] = 'created';
                    \DB::table('lottery_chase_order_sub_temp')->insert($v);
                    $ids[] = $v['id'];
                }catch (\Exception $e) {
//                    \DB::table('lottery_chase_order_sub_temp')->where('id','=',$v['id'])->delete();
//                    \DB::table('lottery_chase_order_sub_temp')->insert($v);
                }
                LotteryChaseOrder::where('chase_number',$v['chase_number'])->update(['complete_periods'=>\DB::raw('complete_periods + 1')]);
            }
//            $ids = array_column($data,'id');
            \DB::table('lottery_chase_order_sub')->whereIn('id',$ids)->update(['state'=>'created']);
            $this->logger->debug('追号补创建数据个数: '.count($data));
        }
    }

    public function runReopen(){
        //补生成追号单
        $this->chaseReCreate();
        $this->chaseReCreate2();
        // 查询该期追号单
        $list = \DB::table('lottery_chase_order_sub_temp AS chase_sub')->leftJoin('lottery_chase_order AS chase','chase_sub.chase_number','=','chase.chase_number')
            ->groupBy(['chase.lottery_id','chase_sub.lottery_number'])
            ->get([
                'chase.lottery_id',
                'chase_sub.lottery_number',
            ])->toArray();
        $settle = new Settle($this->ci);
        $data = [];
        foreach ($list as $v) {
            $data[] = $settle->runByNotifyV2($v->lottery_id, $v->lottery_number);
        }

        $this->logger->debug('追号补结算数据个数: '.count($data));
    }

    /**
     * 取消追号
     * @param  integer $chaseNumber [description]
     * @return [type]               [description]
     */
    public function cancel($chaseNumber, $user_id) {
        try {
            $this->db->getConnection()->beginTransaction();
            if ($this->db->getConnection()->transactionLevel()) {
                $user = User::find($user_id);
                if(!$user){
                    throw new \Exception($chaseNumber . " user error!!", 2);
                }

                $chaseOrder = \DB::table('lottery_chase_order')
                    ->where('user_id', $user_id)
                    ->where('chase_number', $chaseNumber)
                    ->first();
                if (!empty($chaseOrder)) {
                    $res = \DB::table('lottery_chase_order')->where('id', $chaseOrder->id)->lockForUpdate()->first();
                    if ($res->state == 'cancel') {
                        $this->db->getConnection()->rollback();
                        throw new \Exception($chaseNumber . " chase number error!!", 2);
                    }
                } else {
                    $this->db->getConnection()->rollback();
                    throw new \Exception($chaseNumber . " chase number error!!", 2);
                }

                $result = \DB::table('lottery_chase_order_sub')
                    ->where('chase_number', $chaseNumber)
                    ->where('state','=','default')
                    ->first([\DB::raw('SUM(pay_money) AS sum_money'),\DB::raw('COUNT(1) AS total')]);

                if ($result->sum_money == 0 || $result->total == 0) {
                    $this->db->getConnection()->rollback();
                    throw new \Exception($chaseNumber . " chase number error!!", 2);
                }
                // 更新追号订单
                $r1 = \DB::table('lottery_chase_order_sub')
                    ->where('chase_number', $chaseNumber)
                    ->where('state','=','default')
                    ->update(['state'=>'cancel']);

                $r2 = \DB::table('lottery_chase_order')
                    ->where('user_id', $user_id)
                    ->where('chase_number', $chaseNumber)
                    ->update(['state' => 'cancel']);

                //$memo = "停止追号-追单号:" . $chaseNumber . "-取消总期:" . $result->total;
                $memo = $this->lang->text("Stop chasing No. - Chase No.: %s - cancel total period: %s", [$chaseNumber, $result->total]);
                $wallet = new Wallet($this->ci);
                // 锁定钱包
                $funds = \Model\Funds::where('id', $user->wallet_id)->lockForUpdate()->first();
                $r3 = $wallet->crease($user->wallet_id, $result->sum_money);
                //流水里面添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($this->ci);
                $dmlData = $dml->getUserDmlData($user->id);
                $r4 = \Model\FundsDealLog::create([
                    "user_id" => $user_id,
                    "user_type" => 1,
                    "username" => $user->name,
                    "order_number" => $chaseNumber,
                    "deal_type" => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
                    "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                    "deal_money" => $result->sum_money,
                    "balance" => intval($funds['balance'] + $result->sum_money),
                    "memo" => $memo,
                    "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                    'total_bet'=>$dmlData->total_bet,
                    'withdraw_bet'=> 0,
                    'total_require_bet'=>$dmlData->total_require_bet,
                    'free_money'=>$dmlData->free_money
                ]);
                $this->db->getConnection()->commit();
                return true;
            } else {
                $this->db->getConnection()->rollback();
            }
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
        }
        $this->db->getConnection()->rollback();
        return false;
    }

    /**
     * 取消试玩追号
     * @param  integer $chaseNumber [description]
     * @return [type]               [description]
     */
    public function trialCancel($chaseNumber, $user_id) {
        try {
            $this->db->getConnection()->beginTransaction();
            if ($this->db->getConnection()->transactionLevel()) {
                $user = \DB::table('trial_user')->find($user_id);
                if($user) {
                    // 锁定钱包
                    $funds = (array)\DB::table('trial_funds')->where('id', $user->wallet_id)->lockForUpdate()->first();

                    $chaseOrder = \DB::table('lottery_trial_chase_order')
                        ->where('user_id', $user_id)
                        ->where('chase_number', $chaseNumber)
                        ->first();

                    if (!empty($chaseOrder)) {
                        $res = \DB::table('lottery_trial_chase_order')->where('id', $chaseOrder->id)->lockForUpdate()->first();
                        if ($res->state == 'cancel') {
                            $this->db->getConnection()->rollback();
                            throw new \Exception($chaseNumber . " chase number error!!", 2);
                        }
                    } else {
                        $this->db->getConnection()->rollback();
                        throw new \Exception($chaseNumber . " chase number error!!", 2);
                    }

                    $result = \DB::table('lottery_trial_chase_order_sub')
                        ->where('chase_number', $chaseNumber)
                        ->where('state', '=', 'default')
                        ->first([\DB::raw('SUM(pay_money) AS sum_money'), \DB::raw('COUNT(1) AS total')]);

                    if ($result->sum_money == 0 || $result->total == 0) {
                        $this->db->getConnection()->rollback();
                        throw new \Exception($chaseNumber . " chase number error!!", 2);
                    }
                    // 更新追号订单
                    $r1 = \DB::table('lottery_trial_chase_order_sub')
                        ->where('chase_number', $chaseNumber)
                        ->where('state', '=', 'default')
                        ->update(['state' => 'cancel']);

                    $r2 = \DB::table('lottery_trial_chase_order')
                        ->where('user_id', $user_id)
                        ->where('chase_number', $chaseNumber)
                        ->update(['state' => 'cancel']);

                    //$memo = "停止追号-追单号:" . $chaseNumber . "-取消总期:" . $result->total;
                    $memo = $this->lang->text("Stop chasing No. - Chase No.: %s - cancel total period: %s", [$chaseNumber, $result->total]);
                    \DB::table('trial_funds')->where('id', $user->wallet_id)->update(['balance_before' => \DB::raw('balance'),
                        'balance' => \DB::raw("balance + {$result->sum_money}")]);
                    //流水里面添加打码量可提余额等信息
                    $r4 = \DB::table('funds_trial_deal_log')->insert([
                        "user_id" => $user_id,
                        "user_type" => 1,
                        "username" => $user->name,
                        "order_number" => $chaseNumber,
                        "deal_type" => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
                        "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                        "deal_money" => $result->sum_money,
                        "balance" => intval($funds['balance'] + $result->sum_money),
                        "memo" => $memo,
                        "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                    ]);

                    $this->db->getConnection()->commit();
                }
            }else {
                $this->db->getConnection()->rollback();
            }
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
        }
        return false;
    }
}