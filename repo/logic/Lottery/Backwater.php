<?php
namespace Logic\Lottery;

use Logic\Recharge\Recharge;
use Logic\Admin\Message;
use Logic\Logic;

class Backwater extends Logic{

    public function sendBackWater($param){
        $batchNo=$param['batch_no'];
        $active_type=$param['active_type'];

        $obj = new \Logic\Recharge\Recharge($this->ci);
        $message = new Message($this->ci);

        $check=\DB::table('active_backwater')->where('batch_no',$batchNo)->first(['id','type','active_type']);

        if(!$check){
            $this->logger->error('返水审核表暂无数据,批次号:'.$batchNo);
            return false;
        }
        if($active_type == 1){
            $activeApply=\DB::table('active_apply as ap')
                            ->leftJoin('active as a','ap.active_id','=','a.id')
                            ->where('ap.batch_no',$batchNo)
                            ->where('ap.status','=','undetermined')
                            ->get(['ap.batch_no','ap.user_name','ap.id','ap.user_id','ap.coupon_money','ap.deposit_money','a.type_id','a.name'])
                            ->toArray();
            if(empty($activeApply)){
                $this->logger->error('发放活动返水错误,批次号:'.$batchNo.'暂无数据');
                return false;
            }

            //修改活动状态
            \DB::table('active_apply') ->where('batch_no',$batchNo)
               ->where('status','=','undetermined')
               ->update(['status'=>'pending']);

            //发放站内信
            foreach($activeApply as $value){
               $content = "";
               switch($value->type_id){
                    case 8:
                        //周返水 发送回水信息
                        $content  = ["Dear user, congratulations on your weekly rebate amount of %s, The more games, the more rebates. If you have any questions, please consult our 24-hour online customer service", $value->coupon_money/100];
                        break;
                    case 9:
                        //月返水 发送回水信息
                        $content  = ["Dear user, congratulations on your monthly rebate amount of %s, The more games, the more rebates. If you have any questions, please consult our 24-hour online customer service", $value->coupon_money/100];
                        break;
                }

                $insertId = $obj->messageAddByMan('Backwater news', $value->user_name, $content,$value->user_id,13,$value->id);
                $message->messagePublish($insertId);
            }
        }elseif($active_type == 2){
            if($check->active_type == 2 && $check->type == 1){
                $res=\DB::table('rebet')
                        ->selectRaw('user_id,user_name,batch_no,sum(bet_amount) as bet_money,sum(rebet) as coupon_money,1 as type')
                        ->where('batch_no',$batchNo)
                        ->where('status','=',1)
                        ->groupBy('user_id')
                        ->get()
                        ->toArray();
            }elseif($check->active_type == 2 && $check->type ==4){
                $res=\DB::table('user_monthly_award')
                        ->selectRaw('id,user_id,user_name,batch_no,bet_money,award_money as coupon_money, 2 as type')
                        ->where('batch_no',$batchNo)
                        ->where('status','=',1)
                        ->get()
                        ->toArray();
            }else{
                return false;
            }
            if(empty($res)){
                $this->logger->error('会员等级返水错误,批次号:'.$batchNo.'暂无数据');
            }

            if($check->active_type == 2 && $check->type == 1){
                \DB::table('rebet')->where('batch_no',$batchNo)->where('status','=',1)->update(['status'=>2]);
            }elseif($check->active_type == 2 && $check->type ==4){
                \DB::table('user_monthly_award')->where('batch_no',$batchNo)->where('status','=',1)->update(['status'=>2]);
            }else{
                return false;
            }

            foreach($res as $val){
                switch($val->type){
                    case 1:
                        //日返水内容
                        $content =["Dear user, congratulations on your daily rebate amount of %s, The more games, the more rebates. If you have any questions, please consult our 24-hour online customer service", $val->coupon_money];
                        $insertId = $obj->messageAddByMan('Backwater news', $val->user_name, $content,$val->user_id,14,$batchNo);
                        $message->messagePublish($insertId);
                        break;
                    case 2:
                        //月返水内容
                        $title = $this->ci->get('settings')['website']['name'] ?? '';//标题
                        $content =["Dear %s, your monthly salary and the system has sent %s yuan to your accountHere %s",  $val->user_name, $val->coupon_money / 100, $title];
                        $insertId = $obj->messageAddByMan('Level monthly reward', $val->user_name, $content,$val->user_id,16,$val->id);
                        $message->messagePublish($insertId);
                        break;
                    default;
                        return false;
                }
            }
        }else{
            return false;
        }
        //修改返水审核状态
        \DB::table('active_backwater')->where('batch_no',$batchNo)->update(['status'=>2,'send_time'=>date('Y-m-d H:i:s',time())]);

        return true;
    }
}