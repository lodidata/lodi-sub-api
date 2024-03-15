<?php

use Logic\Logic;
use Logic\Wallet\Wallet;
use Model\FundsDealLog;
use Utils\Www\Action;
use Model\MessagePub;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "返水审核领取";
    const DESCRIPTION = "返水审核领取";
    const TAGS = "返水审核";
    const PARAMS = [
       "id" => "string () #"
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId=$this->auth->getUserId();

        $id = $this->request->getParam('id');
        $key=$userId.'_'.$id;
        if ($this->redis->incr($key) > 1) {
            return $this->lang->set(204);
        }
        $user      = \DB::table('user')->where('id',$userId)->first();

        $active_type = $this->request->getParam('active_type',13);
        if($active_type == 13){
            //活动返水
            $active=DB::table('active_apply as ap')
                      ->leftJoin('active as a','ap.active_id','=','a.id')
                      ->where('ap.id',$id)
                      ->where('ap.user_id',$userId)
                      ->where('ap.status','=','pending')
                      ->first(['ap.user_id','ap.coupon_money','ap.withdraw_require','ap.batch_no','a.type_id']);
        }elseif($active_type == 14){
            //日返水
            $active=DB::table('rebet')
                      ->where('batch_no',$id)
                      ->where('user_id','=',$userId)
                      ->where('status','=',2)
                      ->selectRaw('user_id,sum(rebet)*100 as coupon_money,sum(dml_amount)*100 as withdraw_require,batch_no,1 as type_id')
                      ->groupBy('user_id')
                      ->first();
        }elseif($active_type == 16){
            //月俸禄
            $active=DB::table('user_monthly_award')
                        ->where('id',$id)
                        ->where('user_id',$userId)
                        ->where('status','=',2)
                        ->selectRaw('user_id,award_money as coupon_money,dml_amount as withdraw_require,batch_no,2 as type_id')
                        ->first();
        }elseif($active_type == 17){
            //周薪
            $active=DB::table('user_week_award')
                ->where('id',$id)
                ->where('user_id',$userId)
                ->where('status','=',2)
                ->selectRaw('user_id,award_money as coupon_money,dml_amount as withdraw_require,batch_no,3 as type_id')
                ->first();
        }elseif($active_type == 18){
            //晋升彩金
            $active=DB::table('user_level_winnings')
                ->where('level',$id)
                ->where('user_id',$userId)
                ->where('status','=',2)
                ->selectRaw('user_id,money as coupon_money,dml_amount as withdraw_require,10 as type_id')
                ->first();
            $level = \DB::table('user_level')->where('level',$user->ranting)->first();
        }else{
            return $this->lang->set(13);
        }
        $this->redis->expire($key, 10);
        if(empty($active)){
            return $this->lang->set(10015);
        }
        try {
            $this->db->getConnection()->beginTransaction();
            switch($active->type_id){
                case 1:
                    $deal_type=701;
                    $dateStr='日返水';
                    break;
                case 2:
                    $deal_type=FundsDealLog::TYPE_LEVEL_MONTHLY;
                    $dateStr='月薪';
                    break;
                case 3:
                    $deal_type=FundsDealLog::TYPE_LEVEL_WEEK;
                    $dateStr='周薪';
                    break;
                case 8:
                    $deal_type =702;
                    $dateStr='周活动';
                    break;
                case 9:
                    $deal_type =703;
                    $dateStr='月活动';
                    break;
                case 10:
                    $deal_type=FundsDealLog::TYPE_LEVEL_MANUAL1;
                    $dateStr='晋升彩金';
                    break;
                default:
                    $deal_type =703;
                    $dateStr='月';
            }
            $rand        = random_int(10000, 99999);
            $orderNumber = date('Ymdhis') . str_pad(random_int(1, $rand), 4, '0', 0);
            $user = (array)\DB::table('user')->where('id',$active->user_id)->first(['id','name','wallet_id','ranting']);
            \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            $money=$active->coupon_money;
            $total_require_bet=$active->withdraw_require;
            if ($active_type == 18) $total_require_bet = $money * $level->upgrade_dml_percent / 10000;
            $memo = "{$dateStr}返水活动: 用户:" . $user['name'] . ",金额:" . $money /100;
            $wallet = new Wallet($this->ci);
            $wallet->addMoney($user, $orderNumber, $money, $deal_type, $memo, $total_require_bet);
            DB::table('rebet_deduct')->where('user_id', $active->user_id)
                ->where('batch_no', $active->batch_no)
                ->where('type', $deal_type)
                ->update([
                    'order_number' => $orderNumber,
                ]);

            if($active->type_id == 1){
                $activeData=array(
                    'status'=>3,
                    'process_time'=>date('Y-m-d H:i:s',time())
                );
                DB::table('rebet')->where('batch_no',$id)->where('user_id',$userId)->update($activeData);

                \Model\UserData::where('user_id', $userId)->increment('rebet_amount', $money);
            }elseif($active->type_id == 2){
                $activeData=array(
                    'status'=>3,
                    'process_time'=>date('Y-m-d H:i:s',time())
                );
                DB::table('user_monthly_award')->where('id',$id)->where('user_id',$userId)->update($activeData);
            }elseif($active->type_id == 3){
                $activeData=array(
                    'status'=>3,
                    'process_time'=>date('Y-m-d H:i:s',time())
                );
                DB::table('user_week_award')->where('id',$id)->where('user_id',$userId)->update($activeData);
            }elseif($active->type_id == 10){
                $activeData=array(
                    'status'=>3,
                    'process_time'=>date('Y-m-d H:i:s',time())
                );
                DB::table('user_level_winnings')->where('level',$id)->where('user_id',$userId)->update($activeData);
            }else{
                $activeData=array(
                    'status'=>'pass',
                    'process_time'=>date('Y-m-d H:i:s',time())
                );
                DB::table('active_apply')->where('id',$id)->where('user_id',$userId)->update($activeData);
                \Model\UserData::where('user_id', $user['id'])->increment('rebet_amount', $money);
            }
            if (!in_array($active->type_id,[2,3,10])){
                DB::table('active_backwater')
                    ->where('batch_no',$active->batch_no)
                    ->update(['receive_cnt'=>DB::raw('receive_cnt +1'),'receive_amount'=>DB::raw("receive_amount + {$active->coupon_money}")]);
            }
            $this->db->getConnection()->commit();
        }catch(Exception $exception){
            $this->logger->error("领取返水失败,领取id:".$id."\r\n" .$exception->getMessage());
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }
};