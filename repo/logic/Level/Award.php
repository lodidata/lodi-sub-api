<?php
/**
 * 对不同的层级进行月俸禄奖励
 * User: Taylor
 * Date: 2018/12/26
 */

namespace Logic\Level;

use DB;
use Model\FundsDealLog;

/**
 * 月俸禄模块
 */
class Award extends \Logic\Logic {

    /**
     * 获取层级列表
     * @author Taylor 2018-12-27
     */
    public function get_levels(){
        $level = DB::table('user_level')->selectRaw('level,lottery_money,monthly_money,monthly_percent,monthly_recharge,upgrade_dml_percent,week_money,week_recharge')->orderBy('id','asc')->get()->toArray();
        return array_column($level,NULL,'level');
    }

    /**
     * 月俸禄执行程序
     * @author Taylor 2018-12-27
     */
    public function monthly_award($runMode='sendAward'){
        //获取上月初和月末
        $last_month = strtotime('last month');
        $f_day = date('Y-m-01', $last_month);//月初
        //加锁
        $lock_key = $this->redis->setnx('send_award_lock'.$f_day,1);
        if ($runMode == 'sendAward' && !$lock_key) {
            $this->logger->info($f_day.'【月俸禄】已经计算月俸禄'.date('Y-m-d H:i:s'));
            return false;
        }
        $this->redis->expire('send_award_lock'.$f_day,86400);
        $l_day = date('Y-m-t', $last_month);//月末
        $levels = $this->get_levels();//获取层级列表
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $page = 1;
        $title = $this->ci->get('settings')['website']['name'];//标题
        $this->db->getConnection()->beginTransaction();
        $batchNo=time();
        while (1) {
            $lottery_monthly = DB::table('rpt_user')->where([
                ['count_date', '>=', $f_day],
                ['count_date', '<=', $l_day],
            ])->selectRaw('user_id,user_name,sum(deposit_user_amount)*100 as recharge_money')
                ->where('deposit_user_amount', '>', 0)
                ->groupBy('user_id')->forPage($page, 1000)->orderBy('user_id', 'asc')->get()->toArray();
            $page++;
            if(!$lottery_monthly || count($lottery_monthly) <= 0) break;
            try {
                $data = [];
                foreach ($lottery_monthly as $m) {
                    $user = (array)DB::table('user')->where('id', $m->user_id)->first();//用户的等级
                    $level = (array)$levels[$user['ranting']];//当前等级信息
                    if ($runMode == 'sendAward' && $level['monthly_money'] && $m->recharge_money >= $level['monthly_recharge']) {//投注金额达到月俸傉的条件
                        $deal_no = FundsDealLog::generateDealNumber();
                        $memo = "赠送{$user['name']}," . date('Ym', strtotime($f_day)) . '的月俸禄' . ($level['monthly_money'] / 100) . '元';
                        $data = [//月俸禄记录
                            'award_date' => $f_day,
                            'user_id' => $m->user_id,
                            'user_name' => $m->user_name,
                            'bet_money' => $m->recharge_money,
                            'award_money' => $level['monthly_money'],
                            'level'       => $user['ranting'],
                            'batch_no'    => $batchNo,
                            'status'      => 2,
                            'dml_amount'  => $level['monthly_money'] * $level['upgrade_dml_percent'] / 10000
                        ];
                        $monthly_id = \DB::table('user_monthly_award')->insertGetId($data);
                        \DB::table('user_data')->where('user_id', $m->user_id)->update(['monthly_award_id' => $monthly_id]);

//                        $wallet->addMoney($user, $deal_no, $level['monthly_money'], FundsDealLog::TYPE_LEVEL_MONTHLY, $memo, $level['monthly_money'] * $level['upgrade_dml_percent'] / 10000);//赠送相应月俸禄，写流水
                        //发信息
                        $to_day = date('Y-m-d',time());
                        $content=$this->lang->text('We would like to inform you that your VIP reward for this month has been issued and is ready for collection. Monthly Bonus: %s Promotion Bonus: %s', [$to_day, $level['monthly_money']/100, $title]);

                        $exchange = 'user_message_send';
                        \Utils\MQServer::send($exchange,[
                            'user_id'       => $m->user_id,
                            'user_name'     => $m->user_name,
                            'active_type'   => 17,
                            'active_id'     => $monthly_id,
                            'title'         => $this->lang->text('Level monthly reward'),
                            'content'       => $content,
                        ]);
                    }
                }
//                DB::table('user_monthly_award')->insert($data);
                $this->db->getConnection()->commit();
            } catch (\Exception $e) {
                $this->db->getConnection()->rollback();
            }
        }

//        $activeData=\DB::table('user_monthly_award')
//            ->selectRaw('count(1) as cnt,sum(award_money) as back_amount')
//            ->where('batch_no',$batchNo)
//            ->first();
//        $backData=array(
//            'batch_no'  =>$batchNo,
//            'type'      =>4,
//            'active_type'=> 2,
//            'batch_time'=>$f_day,
//            'back_cnt'  => $activeData->cnt,
//            'back_amount'=>$activeData->back_amount ?? 0,
//        );
//        if($activeData->cnt ==0){
//            $backData['status'] = 2;
//            $backData['send_time']=date('Y-m-d H:i:s',time());
//        }
//        \DB::table('active_backwater')->insert($backData);

    }
    /**
     * 周薪执行程序
     * @author Taylor 2018-12-27
     */
    public function week_award($runMode='sendAward',$startTime=  null, $endTime=null){

        //获取周一和周日

        if(!$startTime){
            //获取周一的时间
            if(date('w') == 1){
                $startTime = date("Y-m-d", strtotime('last monday'));
            }else{
                $startTime = date("Y-m-d", strtotime('-1 week last monday'));
            }
        }
        $deal_type = 702;    //701-日回水，702-周回水，703-月回水
        //传入结束时间
        !$endTime && $endTime   = date('Y-m-d', strtotime("-1 sunday",time()));

        //加锁
        $lock_key = $this->redis->setnx('send_award_lock'.$startTime,1);
        if ($runMode == 'sendAward' && !$lock_key) {
            $this->logger->info($startTime.'【周薪】已经计算周薪'.date('Y-m-d H:i:s'));
            return false;
        }
        $this->redis->expire('send_award_lock'.$startTime,86400);

        $levels = $this->get_levels();//获取层级列表
        $page = 1;
        $title = $this->ci->get('settings')['website']['name'];//标题
        $batchNo=time();
        while (1) {
            $lottery_monthly = DB::table('rpt_user')->where([
                ['count_date', '>=', $startTime],
                ['count_date', '<=', $endTime],
            ])->selectRaw('user_id,user_name,sum(deposit_user_amount)*100 as recharge_money')
                ->where('deposit_user_amount', '>', 0)
                ->groupBy('user_id')->forPage($page, 1000)->orderBy('user_id', 'asc')->get()->toArray();
            $page++;
            if(!$lottery_monthly || count($lottery_monthly) <= 0) break;
            foreach ($lottery_monthly as $m) {
                try {
                    $user = (array)DB::table('user')->where('id', $m->user_id)->first(['ranting']);//用户的等级
                    $level = (array)$levels[$user['ranting']];//当前等级信息
                    if ($runMode == 'sendAward' && $level['week_money'] && $m->recharge_money >= $level['week_recharge']) {//投注金额达到周薪的条件
                        $data = [//周薪记录
                            'award_date'  => $startTime,
                            'user_id'     => $m->user_id,
                            'user_name'   => $m->user_name,
                            'bet_money'   => $m->recharge_money,
                            'award_money' => $level['week_money'],
                            'batch_no'    => $batchNo,
                            'level'       => $user['ranting'],
                            'status'      => 2,
                            'dml_amount'  => $level['week_money'] * $level['upgrade_dml_percent'] / 10000
                        ];
                        $week_id = \DB::table('user_week_award')->insertGetId($data);
                        \DB::table('user_data')->where('user_id', $m->user_id)->update(['week_award_id' => $week_id]);
                        //发送周薪信息
                        $to_day = date('Y-m-d',time());
                        $content=$this->lang->text('We would like to inform you that your VIP reward for this week has been issued and is ready for collection. Weekly Bonus: %s Promotion Bonus: %s', [$to_day, $level['week_money']/100, $title]);
                        $exchange = 'user_message_send';
                        \Utils\MQServer::send($exchange,[
                            'user_id'     => $m->user_id,
                            'user_name'   => $m->user_name,
                            'active_type' => 17,
                            'active_id'   => $week_id,
                            'title'       => $this->lang->text('Level week reward'),
                            'content'     => $content,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('发放周薪失败,用户:'.$m->user_id.',时间:'.date('Y-m-d H:i:s').',message:'.$e->getMessage());
                }
            }

        }
    }
}