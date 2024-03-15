<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        //主订单
        $transfer_no = $this->request->getParam('transfer_no', 1);
        $transfer = DB::table('transfer_order')->where('transfer_no', $transfer_no)->first(['withdraw_order','money']);
        if (empty($transfer)) return $this->lang->set(0);
        $data = DB::table('transfer_no_sub')->where('transfer_no', $transfer_no)
            ->get(['id', 'transfer_no','sub_order', 'amount', 'status', 'user_account', 'order_type', 'is_reward', 'created_at', 'updated_at'])->toArray();
        $true_amout = 0;
        if(count($data)<=0) return $this->lang->set(0);
        $time = time();
        foreach ($data as $k => $v) {  //兼容以前的
            if (strlen($v->user_account) > 4) {
                $data[$k]->user_account = '*****'.substr($v->user_account,-4);
            }
            $created_time = strtotime($v->created_at);
            if ($created_time + 12*60 < $time || $v->status == 'confirming') {
                $data[$k]->show_not_received = 1;
            } else {
                $data[$k]->show_not_received = 0;
            }
            if($v->status != 'canceled') {
                $true_amout += $v->amount;
            }
            $data[$k]->remainder = '0.0';
            $data[$k]->reward_amount = (int)($v->amount * 0.05);
            if (in_array($v->status,['waiting','confirming'])) {
                $diff_second = ($created_time + 17 * 60) - time();
                if ($diff_second > 0 && $diff_second <= 17*60) {
                    $data[$k]->remainder = sprintf("%.1f",($diff_second / 60));
                }
            }
            $v->trade_no = $transfer->withdraw_order;
            unset($v->updated_at);
        }

        if ($true_amout < $transfer->money) {
            $data[count($data)]=[
                'id' => 0,
                'transfer_no'=>'0',
                'sub_order' => '0',
                'amount' => $transfer->money - $true_amout,
                'status' => 'pending',
                'order_type' => 2,
                'is_reward' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'trade_no'=>'0'
            ] ;
        }
        $list = [
            'total_amount' => $transfer->money,
            'info' => $data
        ];

        //清除redis
        $user_id = $this->auth->getUserId();
        $redisKey = 'kpay:withdraw_message:'.$user_id;
        $this->redis->lrem($redisKey,0,$transfer_no);

        return $this->lang->set(0, [], $list, []);
    }
};
