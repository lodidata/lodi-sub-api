<?php
namespace Logic\Lottery;

use Logic\Admin\Message;

class BatchLottery extends \Logic\Logic {

    //新版彩金
    public function batchSendLottery(){
        $ip = \Utils\Client::getIp();
        //读取立即发放彩金的活动
        $immediately = $this->redis->smembers("immediately_send_handsel");
        if (!empty($immediately)) {
            foreach ($immediately as $aid) {
                //读取活动对应的发放用户列表
                $len = $this->redis->llen("immediately_send_handsel_item:".$aid);
                if ($len <= 0) {
                    $this->redis->srem("immediately_send_handsel", $aid);    //如果该活动下发放用户列表为空，则表示该活动已经被发放过了，则删除掉集合中活动id
                    continue;
                }
                //获取该条活动的具体信息
                $act_info = (array)\DB::table("active_handsel")->where('status',1)->whereRaw('id=?', [$aid])->first();
                if (empty($act_info)) {
                    continue;
                }
                //先修改本条活动的发放状态
                $up = \DB::table("active_handsel")->where('status',1)->whereRaw('id=? and state=?', [$aid, 0])->update(['state'=>1]);
                if ($up) {
                    //记录本条活动的发放日志
                    $count_amount = $act_info['give_amount'] * $len;    //该批次一共赠送的彩金数
                    $inData = [
                        'active_handsel_id' => $aid,
                        'msg_title' => $act_info['msg_title'],
                        'msg_content' => $act_info['msg_content'],
                        'give_away' => $act_info['give_away'],
                        'notice_away' => $act_info['notice_type'],
                        'give_num' => $len,
                        'give_amount' => $act_info['give_amount'],
                        'dm_num' => $act_info['dm_num'],
                        'total_give_amount' => $count_amount,
                        'create_time' => date("Y-m-d H:i:s"),
                        'give_time' => date("Y-m-d H:i:s"),
                        'valid_time' => $act_info['valid_time'],
                    ];
                    $handsel_id = \DB::table('active_handsel_log')->insertGetId($inData);

                    //循环处理每个用户的发放彩金
                    for ($i = 1; $i<=$len; $i++) {
                        $itm = $this->redis->lpop("immediately_send_handsel_item:".$aid);
                        $fmt_itm = explode(':', $itm);   //itm是由 uid:username 拼接起来的字符串
                        $cur_uid = $fmt_itm[0];     //用户uid
                        $cur_uname = $fmt_itm[1];    //用户名
                        //发放彩金
                        $memo = '批量彩金赠送';
                        $obj = new \Logic\Recharge\Recharge($this->ci);
//                        $re = $obj->handSendCoupon($cur_uid, $act_info['dm_num'], $act_info['give_amount'], $memo, $ip);
                        //发送通知
                        $noticeType = explode(',', $act_info['notice_type']);
                        if (in_array(3, $noticeType)) {
                            $message = new Message($this->ci);
                            $insertId = $obj->messageAddByMan($act_info['msg_title'], $cur_uname, $act_info['msg_content'],0,12,$act_info['id']);
                            $message->messagePublish($insertId);
                        }
                        //添加用户待领取彩金记录
                        \DB::table('handsel_receive_log')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'handsel_log_id' => $handsel_id,
                            'status' => 0,
                            'create_time' => date("Y-m-d H:i:s"),
                            'limit_game' => $act_info['limit_game'],
                            'recharge_num' => $act_info['recharge_num'],
                            'valid_time' => $act_info['valid_time'],
                            'receive_amount' => $act_info['give_amount'],
                            'dm_num' => $act_info['dm_num'],
                        ]);

                        //添加用户参加活动的记录
                        \DB::table('active_apply')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'active_id' => $act_info['active_id'],
                            'apply_time' => date('Y-m-d H:i:s'),
                            'active_name' => 'give away bonus',
                            'coupon_money' => $act_info['give_amount'],   //赠送金额
                            'withdraw_require' => $act_info['dm_num'],   //取款打码量
                            'memo' => $memo,
                            'status' => 'pending',
                            'state' => 'manual',
                            'process_time' => date('Y-m-d H:i:s'),
                            'created' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        //读取定时发放彩金的活动
        $timer = $this->redis->smembers("timer_send_handsel");
        if (!empty($timer)) {
            foreach ($timer as $aid) {
                //读取活动对应的发放用户列表
                $len = $this->redis->llen("timer_send_handsel_item:".$aid);
                if ($len <= 0) {
                    $this->redis->srem("timer_send_handsel", $aid);    //如果该活动下发放用户列表为空，则表示该活动已经被发放过了，则删除掉集合中活动id
                    continue;
                }
                //获取该条活动的具体信息
                $act_info = (array)\DB::table("active_handsel")->where('status',1)->whereRaw('id=?', [$aid])->first();
                if (empty($act_info)) {
                    continue;
                }
                //判断当前时间是否等于定时发放时间
                $cur_dt = date("Y-m-d H:i");    //最小按分钟匹配
                $timer_dt = date("Y-m-d H:i", strtotime($act_info['give_amount_time']));    //将表中的发送时间格式化到分钟
                if ($act_info['is_now_give'] != 1 && ($timer_dt > $cur_dt)) {
                    continue;
                }

                //检测活动是否被禁用或删除(不是立即发放的活动，有可能管理端后面会禁用或删除配置)
                $active_data = (array)\DB::table('active')->where('id',$act_info['active_id'])->first();
                if (empty($active_data) || !isset($active_data['status']) || ($active_data['status'] != "enabled")) {
                    $this->redis->srem("timer_send_handsel", $aid);
                    continue;
                }
                //先修改本条活动的发放状态
                $up = \DB::table("active_handsel")->where('status',1)->whereRaw('id=? and state=?', [$aid, 0])->update(['state'=>1]);
                if ($up) {
                    //记录本条活动的发放日志
                    $count_amount = $act_info['give_amount'] * $len;    //该批次一共赠送的彩金数
                    $inData = [
                        'active_handsel_id' => $aid,
                        'msg_title' => $act_info['msg_title'],
                        'msg_content' => $act_info['msg_content'],
                        'give_away' => $act_info['give_away'],
                        'notice_away' => $act_info['notice_type'],
                        'give_num' => $len,
                        'give_amount' => $act_info['give_amount'],
                        'dm_num' => $act_info['dm_num'],
                        'total_give_amount' => $count_amount,
                        'create_time' => date("Y-m-d H:i:s"),
                        'give_time' => date("Y-m-d H:i:s"),
                        'valid_time' => $act_info['valid_time'],
                    ];
                    $handsel_id = \DB::table('active_handsel_log')->insertGetId($inData);

                    //循环处理每个用户的发放彩金
                    for ($i = 1; $i<=$len; $i++) {
                        $itm = $this->redis->lpop("timer_send_handsel_item:".$aid);
                        $fmt_itm = explode(':', $itm);   //itm是由 uid:username 拼接起来的字符串
                        $cur_uid = $fmt_itm[0];     //用户uid
                        $cur_uname = $fmt_itm[1];    //用户名
                        //发放彩金
                        $memo = '批量彩金赠送';
                        $obj = new \Logic\Recharge\Recharge($this->ci);
//                        $re = $obj->handSendCoupon($cur_uid, $act_info['dm_num'], $act_info['give_amount'], $memo, $ip);
                        //发送通知
                        $noticeType = explode(',', $act_info['notice_type']);
                        if (in_array(3, $noticeType)) {
                            $message = new Message($this->ci);
                            $insertId = $obj->messageAddByMan($act_info['msg_title'], $cur_uname, $act_info['msg_content'],0,12,$act_info['id']);
                            $message->messagePublish($insertId);
                        }
                        //添加用户待领取彩金记录
                        \DB::table('handsel_receive_log')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'handsel_log_id' => $handsel_id,
                            'status' => 0,
                            'create_time' => date("Y-m-d H:i:s"),
                            'limit_game' => $act_info['limit_game'],
                            'recharge_num' => $act_info['recharge_num'],
                            'valid_time' => $act_info['valid_time'],
                            'receive_amount' => $act_info['give_amount'],
                            'dm_num' => $act_info['dm_num'],
                        ]);
                        //添加用户参加活动的记录
                        \DB::table('active_apply')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'active_id' => $act_info['active_id'],
                            'apply_time' => date('Y-m-d H:i:s'),
                            'active_name' => 'give away bonus',
                            'coupon_money' => $act_info['give_amount'],   //赠送金额
                            'withdraw_require' => $act_info['dm_num'],   //取款打码量
                            'memo' => $memo,
                            'status' => 'pending',
                            'state' => 'manual',
                            'process_time' => date('Y-m-d H:i:s'),
                            'created' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }
    }
}