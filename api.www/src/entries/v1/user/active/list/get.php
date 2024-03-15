<?php

use Model\ActiveSignUp;
use Model\FundsDeposit;
use Utils\Www\Action;
use Model\Active;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取活动列表";
    const DESCRIPTION = "获取优惠活动信息内容列表";
    const TAGS = "优惠活动";
    const QUERY = [
        "active_type_id" => "int(required) #优惠活动分类对应id(0:全部)",
        "active_id" => "int() #活动列表内容对应id唯一",
        "deposit" => "int() #1:充值活动",
    ];
    const SCHEMAS = [
        [
            "id" => "int(required) #id",
            "title" => "string(required) #标题",
            "text" => "string(required) #文本",
            "link" => "string(required) #跳转链接",
            "open" => "int(required) #打开方式(1:弹窗， 2:新窗口， 3:本页面跳转， 4:展开)",
            "img" => "string(required) #图片地址",
            "start_time" => "string(required) #开始时间",
            "end_time" => "string(required) #结束时间",
            "details" => "string(required) #详情",
            "state" => "enum[apply,auto](required) #状态,有apply就显示,没有就不显示(apply:可申请, auto:自动参与)"
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        $isLogin = false;
        if ($verify->allowNext()) {
            $isLogin = true;
            $userId = $this->auth->getUserId();
            $user = (new \Logic\User\User($this->ci))->getUserInfo($userId);
        }
        //参数
        $active_type_id = $this->request->getParam('active_type_id', 0);
        $active_id = $this->request->getParam('active_id', 0);
        $deposit = $this->request->getParam('deposit');

//        $newData = $this->redis->get(\Logic\Define\CacheKey::$perfix['activeList'] . '_' . $active_type_id . '_' . $active_id);
        $newData = [];
        if (!empty($newData)) {
            return json_decode($newData, true);
        }
        $sql = DB::table('active as a')
            ->selectRaw('a.id,a.title,a.description,a.content,a.begin_time,a.link,a.open_mode,a.end_time,a.state,a.cover,a.type_id,a.content,a.content_type,
            a.active_type_id,aty.name as group_name,aty.image as g_img,aty.id as group_id,at.name as template_name,template_id,ar.issue_mode,a.send_times,a.apply_times,a.condition_recharge,a.condition_user_level')
            ->leftJoin('active_template as at', 'a.type_id', '=', 'at.id')
            ->leftjoin('active_rule as ar', 'a.id', '=', 'ar.active_id')
            ->leftjoin('active_type as aty', 'aty.id', '=', 'a.active_type_id')
            ->where('aty.status', '=', 'enabled')
            ->where('a.status', '=', 'enabled')
            ->where('a.type_id', '!=', 12);

        if ($active_id) {
            $sql->where('a.id', '=', $active_id);
        } else {
            if ($active_type_id) {
                $sql->where('a.active_type_id', '=', $active_type_id);
            }

        }


        //只获取充值优惠
        $deposit && $sql->whereIn('a.type_id', [2, 3]);
        $data = $sql
            ->orderBy('a.sort')
            ->orderBy('a.created')
            ->get()
            ->toArray();

        if ($data) {
            $newData = [];
            foreach ($data as $k => $val) {
                $val = (array)$val;
                $tmp['id'] = $val['id'];
                $tmp['type_id'] = $val['type_id'];
                $tmp['title'] = $val['title'];
                $tmp['link'] = $val['link'];
                $tmp['open'] = $val['open_mode'] ?? null;
                $tmp['text'] = $val['description'] ? mergeImageUrl($val['description']) : '';
                $tmp['img'] = showImageUrl( $val['cover']);
                $tmp['start_time'] = $val['begin_time'];
                $tmp['end_time'] = $val['end_time'];
                $tmp['state'] = $val['state'];

                // 如果活动类型为用户申请，需要判断用户当前的申请状态
                if ($val['state'] == 'apply') {
                    if (!$isLogin) {
                        $tmp['apply_status'] = 0; // 未登录直接显示无法参与
                    } else {
                        // 申请获得奖励 状态值3
                        $tmp['apply_status'] = 3;
                        // 无法参与活动 状态值0
                        if ($val['condition_recharge']) {
                            switch ($val['condition_recharge']) {
                                case 2: //当日是否有充值
                                    $rechargeStart = date('Y-m-d 00:00:00');
                                    $rechargeEnd = date('Y-m-d 23:59:59');
                                    break;
                                case 3: //本周是否有充值
                                    $rechargeStart = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
                                    $rechargeEnd = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
                                    break;
                                case 4: //本月是否有充值
                                    $rechargeStart = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y")));
                                    $rechargeEnd = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("t"), date("Y")));
                                    break;
                            }
                            //线上线下充值，手动存款，手动增加余额都算
                            if($val['condition_recharge'] == 1){
                                $rechargeCount = DB::table('funds_deposit')->where(['user_id' => $userId, 'status' => 'paid'])->count() +
                                DB::table('funds_deal_manual')->where(['user_id' => $userId, 'status' => 1, 'type' => ['in', [1, 5]]])->count(); 
                            }else{
                                $rechargeCount = DB::table('funds_deposit')->where(['user_id' => $userId, 'status' => 'paid'])->whereBetween('created', [$rechargeStart , $rechargeEnd])->count() +
                                DB::table('funds_deal_manual')->where(['user_id' => $userId, 'status' => 1, 'type' => ['in', [1, 5]]])->whereBetween('created', [$rechargeStart , $rechargeEnd])->count();
                            }
                            if ($rechargeCount < 1) {
                                $tmp['apply_status'] = 0;
                            }
                        }

                        if ($val['condition_user_level']) {  //用户等级条件
                            $userLevel = $user['ranting'];
                            if (!in_array($userLevel, explode(',', $val['condition_user_level']))) {
                                $tmp['apply_status'] = 0;
                            }
                        }

                        if (!empty($val['apply_times'])) {
                            $applyCount = DB::table('active_apply')
                                ->where(['active_id' => $val['id'], 'user_id' => $userId, 'apply_count_status' => 1, ['created', '>=', date("Y-m-d") . " 00:00:00"], ['created', '<=', date("Y-m-d") . " 23:59:59"]])
                                ->count(); //判断活动最大申请次数 修改限制次数为当日
                            if ($applyCount + 1 > $val['apply_times']) {
                                $tmp['apply_status'] = 0;
                            }
                        }

                        // 已领取 状态值1
                        if ($val['send_times'] == 1) {  //活动赠送次数为一个用户一次时，如果该用户已经领取过该活动，不能再次申请
                            $receiveCount = DB::table('active_apply')->where(['active_id' => $val['id'], 'user_id' => $userId, 'status' => 'pass'])->count();
                            if ($receiveCount) {
                                $tmp['apply_status'] = 1;
                            }
                        }
                        // 申请中待审批 状态值2
                        $pendingApplyCount = DB::table('active_apply')->where(['active_id' => $val['id'], 'user_id' => $userId, 'status' => 'undetermined'])->count();
                        if ($pendingApplyCount) {
                            $tmp['apply_status'] = 2;
                        }
                    }
                }

                // type==7 时添加状态字段判断是否已经申请
                if ($val['type_id'] == 7) {
                    if($isLogin){
                        $tmp['recharge_apply_status'] = 0;
                        // 已充值无法参加
                        $deposit = FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money', '>', 0)->where('user_id', '=', $userId)->first();
                        if ($deposit) {
                            $tmp['recharge_apply_status'] = 1;
                        }
                        // 已参加过无法参加
                        $info = ActiveSignUp::getInfo($userId, $val['id']);
                        if($info){
                            $tmp['recharge_apply_status'] = 1;
                        }
                    }else{
                        $tmp['recharge_apply_status'] = 1;
                    }
                }

                $tmp['template_id'] = $val['template_id'] ?? '';
                $tmp['template_name'] = $val['template_name'] ?? '';
                $tmp['content'] = $val['content'] ?? '';
                $tmp['content_type'] = $val['content_type'] ?? '';
                $tmp['details'] = $val['content'];
                $tmp['active_type_id'] = $val['active_type_id'];
                $tmp['issue_mode'] = $val['issue_mode'];

                $k = intval($val['group_id']);
                // 全部活动
                $newData[0]['group_id'] = 0;
                $newData[0]['group_name'] = $this->lang->text('all offers');
                $newData[0]['data'][] = $tmp;
                $newData[0]['group_img'] = showImageUrl($val['g_img']);
                /*$newData[$k+1]['group_id'] = $val['group_id'];
                $newData[$k+1]['group_name'] = $val['group_name'];
                $newData[$k+1]['group_img'] = $val['g_img'];
                $newData[$k+1]['data'][] = $tmp;*/
            }

        }

        $newData = array_values($newData);
        if ($active_id > 0 && $newData) {
            $newData = $newData[0]['data'];
        }
//        if (!empty($newData)) {
//            $this->redis->setex(\Logic\Define\CacheKey::$perfix['activeList'] . '_' . $active_type_id . '_' . $active_id, 30, json_encode($data));
//        }
        return $newData;
    }

};