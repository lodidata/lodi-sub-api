<?php

use Utils\Www\Action;

return new class() extends Action {
    const TITLE = '批量赠送彩金活动';
    const DESCRIPTION = '领取彩金';
    const QUERY = [
        'title' => 'string() #活动名称',
        'template_id' => 'integer() #模板ID',
    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();

        $params = $this->request->getParams();
        $id = $params['active_id'] ?? 0;            //这个id是消息列表中获取的活动id, 彩金活动是对应active_handsel表中的id，其他活动对应active表中的id

        if (empty($id)) {
            return createRsponse($this->response, 200, -2, '缺少active_id参数');
        }
        $active_handsel = (array)DB::table("active_handsel")->where('id',$id)->first();
        if (empty($active_handsel)) {
            return createRsponse($this->response, 200, -2, '活动信息为空');
        }
        //检查数据
        $handsel_log = (array)DB::table('active_handsel_log')->whereRaw('active_handsel_id=?', [$id])->first();
        if (empty($handsel_log)) {
            return createRsponse($this->response, 200, -2, '活动数据为空');
        }
        $info = (array)DB::table('handsel_receive_log')->whereRaw('handsel_log_id=? and user_id=?', [$handsel_log['id'],$user_id])->first();
        if (empty($info)) {
            return createRsponse($this->response, 200, -2, '活动发放数据为空');
        }
        //判断是否已领取
        if ($info['status'] == 1) {
            return $this->lang->set(204, [], [], []);
        }
        //检测领取时间是否过期
        if (date("Y-m-d H:i:s") > $info['valid_time']) {
            return $this->lang->set(203, [], [], []);
        }
        //判断是否需要充值指定金币才能领取彩金
        if ($active_handsel['recharge_limit'] == 1){
            //单笔充值限制
            if ($active_handsel['recharge_type'] == 1) {
                $recMoney = DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=? and money>0',[$user_id,'paid',$active_handsel['give_amount_time']])->max('money');
                if (empty($recMoney) || $recMoney < $active_handsel['recharge_coin']) {
                    return $this->lang->set(205, [bcdiv($active_handsel['recharge_coin'],100,2)]);
                }
            }
            //累计充值限制
            if ($active_handsel['recharge_type'] == 2) {
                $recMoney = DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?  and money>0',[$user_id,'paid',$active_handsel['give_amount_time']])->sum('money');
                if (empty($recMoney) || $recMoney < $active_handsel['recharge_coin']) {
                    return $this->lang->set(206, [bcdiv($active_handsel['recharge_coin'],100,2)]);
                }
            }
        }
        $this->db->getConnection()->beginTransaction();

        //修改领取状态
        $up = DB::table('handsel_receive_log')->whereRaw('id=? and status=?', [$info['id'],0])->update(['status' => 1, 'receive_time' => date("Y-m-d H:i:s")]);
        if (!$up) {
            return createRsponse($this->response, 200, -2, '领取失败');
        }
        //更新该条彩金活动领取人数
        DB::table('active_handsel_log')->whereRaw('id=?',[$handsel_log['id']])->increment('receive_num', 1);
        //更新active_apply表状态
        DB::table('active_apply')->whereRaw('user_id=? and active_id=?',[$user_id,$active_handsel['active_id']])->update(['status'=>'pass']);
        //发放彩金
        $ip = \Utils\Client::getIp();
        $obj = new \Logic\Recharge\Recharge($this->ci);
        $re = $obj->handSendCoupon($info['user_id'],$info['dm_num'],$info['receive_amount'],$active_handsel['msg_title'],$ip,0,intval($active_handsel['admin_id']),$active_handsel['admin_user']);

        $this->db->getConnection()->commit();

        return $this->lang->set(202, [$info['receive_amount']], $re, []);
    }
};
