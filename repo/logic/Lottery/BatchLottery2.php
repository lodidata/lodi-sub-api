<?php
namespace Logic\Lottery;

use Logic\Admin\Message;

ini_set("memory_limit", "1056M");

class BatchLottery2 extends \Logic\Logic {

    //新版彩金
    public function batchSendLottery2(){
//        $this->logger->info(" 彩金-发放定时任务错误版本： ");
        $ip = \Utils\Client::getIp();

        //非固定模式-立即发放彩金
        $immediatelyUnfix = $this->redis->smembers("immediately_unfixed_keys");
        if (!empty($immediatelyUnfix)) {
            foreach ($immediatelyUnfix as $aid1) {
                $this->redis->srem("immediately_unfixed_keys", $aid1);
                //读取活动对应的发放用户列表
                $len = intval($this->redis->llen("immediately_unfixed_list:".$aid1));
                if (empty($len) || $len <= 0 ) {
                    $this->logger->error("彩金-当前发放活动ID：".$aid1."没有发放对象");
                    continue;
                }
                //获取该条活动的具体信息
                $act_info = (array)\DB::table("active_handsel")->whereRaw('id=?', [$aid1])->first();
                if (empty($act_info)) {
                    continue;
                }
                //先修改本条活动的发放状态
                $up = \DB::table("active_handsel")->whereRaw('id=? and state=?', [$aid1, 0])->update(['state'=>1]);
                if ($up) {
                    //记录本条活动的发放日志
                    $inData = [
                        'active_handsel_id' => $aid1,
                        'msg_title' => $act_info['msg_title'],
                        'msg_content' => $act_info['msg_content'],
                        'give_away' => $act_info['give_away'],
                        'notice_away' => $act_info['notice_type'],
                        'give_num' => $len,
                        'give_amount' => $act_info['give_amount'],
                        'dm_num' => 0,
                        'total_give_amount' => 0,
                        'create_time' => date("Y-m-d H:i:s"),
                        'give_time' => date("Y-m-d H:i:s"),
                        'valid_time' => $act_info['valid_time'],
                    ];
                    $handsel_id = \DB::table('active_handsel_log')->insertGetId($inData);
                    //循环处理每个用户的发放彩金
                    $count_amount = 0;    //该批次一共赠送的彩金数
                    $receive_num1 = 0;
                    for ($i = 1; $i<=$len; $i++) {
                        $itm = $this->redis->lpop("immediately_unfixed_list:".$aid1);
                        $fmt_itm = explode(':', $itm);   //itm是由 uid:username 拼接起来的字符串
                        $cur_uid = $fmt_itm[0];     //用户uid
                        $cur_uname = $fmt_itm[1];    //用户名
                        $cur_amount = $fmt_itm[2];      //赠送彩金
                        $cur_dm = $fmt_itm[3];          //打码量
                        $count_amount = bcadd($count_amount, $cur_amount, 2);
                        $receive_status = 0;    //彩金领取状态
                        $apply_status = 'pending';
                        $apply_state = 'manual';
                        $receive_time = "1970-01-02 00:00:00";
                        $obj = new \Logic\Recharge\Recharge($this->ci);
                        if ($act_info['receive_way'] == 1) {
                            //发送通知
                            $noticeType = explode(',', $act_info['notice_type']);
                            if (in_array(3, $noticeType)) {
                                $message = new Message($this->ci);
                                $insertId = $obj->messageAddByMan($act_info['msg_title'], $cur_uname, $act_info['msg_content'],0,12,$act_info['id']);
                                $message->messagePublish($insertId);
                            }
                        } elseif ($act_info['receive_way'] == 2) {
                            //判断是否需要充值指定金币才能领取彩金
                            if ($act_info['recharge_limit'] == 1){
                                //单笔充值限制
                                if ($act_info['recharge_type'] == 1) {
                                    $recMoney = \DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?',[$cur_uid,'paid',$act_info['give_amount_time']])->max('money');
                                    if (empty($recMoney) || $recMoney < $act_info['recharge_coin']) {
                                        continue;
                                    }
                                }
                                //累计充值限制
                                if ($act_info['recharge_type'] == 2) {
                                    $recMoney = \DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?',[$cur_uid,'paid',$act_info['give_amount_time']])->sum('money');
                                    if (empty($recMoney) || $recMoney < $act_info['recharge_coin']) {
                                        continue;
                                    }
                                }
                            }
                            $receive_status = 1;
                            $apply_status = "pass";
                            $apply_state = "auto";
                            $receive_time = date("Y-m-d H:i:s");
                            $receive_num1 += 1;
                            //发放彩金
                            $obj->handSendCoupon($cur_uid, $cur_dm, $cur_amount, $act_info['msg_title'], $ip, 0, intval($act_info['admin_id']), $act_info['admin_user']);
                        }
                        //添加用户待领取彩金记录
                        \DB::table('handsel_receive_log')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'handsel_log_id' => $handsel_id,
                            'status' => $receive_status,
                            'create_time' => date("Y-m-d H:i:s"),
                            'limit_game' => $act_info['limit_game'],
                            'recharge_num' => $act_info['recharge_num'],
                            'valid_time' => $act_info['valid_time'],
                            'receive_amount' => $cur_amount,
                            'dm_num' => $cur_dm,
                            'receive_time' => $receive_time
                        ]);
                        //添加用户参加活动的记录
                        \DB::table('active_apply')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'active_id' => $act_info['active_id'],
                            'apply_time' => date('Y-m-d H:i:s'),
                            'active_name' => 'give away bonus',
                            'coupon_money' => $cur_amount,   //赠送金额
                            'withdraw_require' => $cur_dm,   //取款打码量
                            'memo' => $act_info['msg_title'],
                            'status' => $apply_status,
                            'state' => $apply_state,
                            'process_time' => date('Y-m-d H:i:s'),
                            'created' => date('Y-m-d H:i:s')
                        ]);
                    }
                    //更新一下本条活动一共赠送的彩金数量、更新该条彩金活动领取人数
                    \DB::table('active_handsel_log')->where('id',$handsel_id)->update(['total_give_amount'=>$count_amount,'receive_num'=>$receive_num1]);
                    unset($act_info);
                }
            }
        }

        //非固定模式-定时发放彩金
        $timerUnfix = $this->redis->smembers("timer_unfixed_keys");
        $this->logger->error("彩金非固定模式-发放集合：".json_encode($timerUnfix));
        if (!empty($timerUnfix)) {
            foreach ($timerUnfix as $aid2) {
                $this->logger->error("彩金非固定模式-当前发放id：".$aid2);
                //读取活动对应的发放用户列表
                $len2 = intval($this->redis->llen("timer_unfixed_list:".$aid2));
                if (empty($len2) || $len2 <= 0) {
                    $this->redis->srem("timer_unfixed_keys", $aid2);    //如果该活动下发放用户列表为空，则删除掉集合中活动id
                    continue;
                }
                //获取该条活动的具体信息
                $act_info2 = (array)\DB::table("active_handsel")->whereRaw('id=?', [$aid2])->first();
                $this->logger->error("彩金非固定模式-当前发放详情：".json_encode($act_info2));
                if (empty($act_info2)) {
                    $this->redis->srem("timer_unfixed_keys", $aid2);  //活动信息为空，也删除集合中的id
                    continue;
                }
                //判断当前时间是否等于定时发放时间
                $cur_dt = date("Y-m-d H:i");    //最小按分钟匹配
                $timer_dt = date("Y-m-d H:i", strtotime($act_info2['give_amount_time']));    //将表中的发送时间格式化到分钟
                if ($act_info2['is_now_give'] != 1 && ($timer_dt > $cur_dt)) {
                    continue;
                }

                //先修改本条活动的发放状态
                $this->logger->error("彩金非固定模式-准备修改发放状态：");
                $up = \DB::table("active_handsel")->whereRaw('id=? and state=?', [$aid2, 0])->update(['state'=>1]);
                if ($up) {
                    //记录本条活动的发放日志
                    $inData = [
                        'active_handsel_id' => $aid2,
                        'msg_title' => $act_info2['msg_title'],
                        'msg_content' => $act_info2['msg_content'],
                        'give_away' => $act_info2['give_away'],
                        'notice_away' => $act_info2['notice_type'],
                        'give_num' => $len2,
                        'give_amount' => 0,
                        'dm_num' => 0,
                        'total_give_amount' => 0,
                        'create_time' => date("Y-m-d H:i:s"),
                        'give_time' => date("Y-m-d H:i:s"),
                        'valid_time' => $act_info2['valid_time'],
                    ];
                    $handsel_id = \DB::table('active_handsel_log')->insertGetId($inData);

                    //循环处理每个用户的发放彩金
                    $count_amount = 0;    //该批次一共赠送的彩金数
                    $receive_num2 = 0;
                    for ($j = 1; $j<=$len2; $j++) {
                        $itm = $this->redis->lpop("timer_unfixed_list:".$aid2);
                        $fmt_itm = explode(':', $itm);   //itm是由 uid:username 拼接起来的字符串
                        $cur_uid = $fmt_itm[0];     //用户uid
                        $cur_uname = $fmt_itm[1];    //用户名
                        $cur_amount = $fmt_itm[2];    //赠送彩金
                        $cur_dm = $fmt_itm[3];    //打码量
                        $count_amount = bcadd($count_amount, $cur_amount, 2);
                        $receive_status = 0;    //彩金领取状态
                        $apply_status = 'pending';
                        $apply_state = 'manual';
                        $receive_time = "1970-01-03 00:00:00";
                        $obj = new \Logic\Recharge\Recharge($this->ci);
                        if ($act_info2['receive_way'] == 1) {
                            //发送通知
                            $noticeType = explode(',', $act_info2['notice_type']);
                            if (in_array(3, $noticeType)) {
                                $message = new Message($this->ci);
                                $insertId = $obj->messageAddByMan($act_info2['msg_title'], $cur_uname, $act_info2['msg_content'],0,12,$act_info2['id']);
                                $message->messagePublish($insertId);
                            }
                        } elseif ($act_info2['receive_way'] == 2) {
                            //判断是否需要充值指定金币才能领取彩金
                            if ($act_info2['recharge_limit'] == 1){
                                //单笔充值限制
                                if ($act_info2['recharge_type'] == 1) {
                                    $recMoney2 = \DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?',[$cur_uid,'paid',$act_info2['give_amount_time']])->max('money');
                                    if (empty($recMoney2) || $recMoney2 < $act_info2['recharge_coin']) {
                                        continue;
                                    }
                                }
                                //累计充值限制
                                if ($act_info2['recharge_type'] == 2) {
                                    $recMoney2 = \DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?',[$cur_uid,'paid',$act_info2['give_amount_time']])->sum('money');
                                    if (empty($recMoney2) || $recMoney2 < $act_info2['recharge_coin']) {
                                        continue;
                                    }
                                }
                            }
                            $receive_status = 1;
                            $apply_status = "pass";
                            $apply_state = "auto";
                            $receive_time = date("Y-m-d H:i:s");
                            $receive_num2 += 1;
                            //发放彩金
                            $re = $obj->handSendCoupon($cur_uid, $cur_dm, $cur_amount, $act_info2['msg_title'], $ip, $act_info2['admin_id'], $act_info2['admin_name']);
                        }
                        //添加用户待领取彩金记录
                        \DB::table('handsel_receive_log')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'handsel_log_id' => $handsel_id,
                            'status' => $receive_status,
                            'create_time' => date("Y-m-d H:i:s"),
                            'limit_game' => $act_info2['limit_game'],
                            'recharge_num' => $act_info2['recharge_num'],
                            'valid_time' => $act_info2['valid_time'],
                            'receive_amount' => $cur_amount,
                            'dm_num' => $cur_dm,
                            'receive_time' => $receive_time
                        ]);
                        //添加用户参加活动的记录
                        \DB::table('active_apply')->insert([
                            'user_id' => $cur_uid,
                            'user_name' => $cur_uname,
                            'active_id' => $act_info2['active_id'],
                            'apply_time' => date('Y-m-d H:i:s'),
                            'active_name' => 'give away bonus',
                            'coupon_money' => $cur_amount,   //赠送金额
                            'withdraw_require' => $cur_dm,   //取款打码量
                            'memo' => $act_info2['msg_title'],
                            'status' => $apply_status,
                            'state' => $apply_state,
                            'process_time' => date('Y-m-d H:i:s'),
                            'created' => date('Y-m-d H:i:s')
                        ]);
                    }
                    //更新一下本条活动一共赠送的彩金数量、已领取人数
                    \DB::table('active_handsel_log')->where('id',$handsel_id)->update(['total_give_amount'=>$count_amount,'receive_num'=>$receive_num2]);
                    $this->redis->srem("timer_unfixed_keys", $aid2);   //发放成功后，删除集合中id
                    unset($act_info2);
                }
            }
        }
    }
}