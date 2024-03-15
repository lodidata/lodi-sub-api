<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '用户申请活动列表';
    const DESCRIPTION = '获取用户申请活动列表';
    const HINT = 'url的?\d替换成记录ID值';
    const QUERY = [
    ];
    const PARAMS = [];
    const SCHEMAS = [
        [
            'id' => 'int    #记录ID',
            'user_name' => 'string   #用户名',
            'mobile' => 'string #手机号码',
            'email' => 'string()  #邮箱',
            'active_name' => 'string    #活动名称',
            'content' => 'string    #申请内容',
            'start_time' => 'string    #开始时间',
            'end_time' => 'string    #结束时间',
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        return $this->getActieApplys($params, $params['page'], $params['page_size']);
    }

    protected function getActieApplys($params, $page = 1, $size = 15)
    {
        $query = DB::table('active_apply as ap')
            ->leftJoin('active as a', 'ap.active_id', '=', 'a.id')
            ->leftJoin('active_template as at', 'a.type_id', '=', 'at.id')
            ->leftJoin('user as u', 'u.id', '=', 'ap.user_id')
            ->leftJoin('admin_user as au', 'au.id', '=', 'ap.created_uid')
            ->select(DB::raw(
                'ap.id,a.type_id,a.active_type_id,ap.user_name,ap.user_id,
                    ap.active_id,a.title,a.name as active_name,
                    ap.deposit_money,ap.coupon_money as money,
                    ap.withdraw_require,ap.apply_time,
                    ap.updated,ap.content,ap.memo,at.name as template,
                    ap.status,ap.state,ap.process_time,
                    a.begin_time,a.end_time,ap.reason,ap.apply_pic,au.username as operator_name',
            ))
            ->whereNotIn('u.tags', [4, 7])
            ->where('a.state', 'apply');


        if (isset($params['user_name']) && !empty($params['user_name'])) {
            $user_name = $params['user_name'];
            $query = $query->where('ap.user_name', "$user_name");
        }

        if(isset($params['active_name']) && !empty($params['active_name'])) {
            $active_name = $params['active_name'];
            $query = $query->where('a.name', "$active_name");
        }

        if (isset($params['active_type_id']) && !empty($params['active_type_id'])) {
            $query = $query->where('a.active_type_id', $params['active_type_id']);
        }

        if (isset($params['start_time']) && !empty($params['start_time'])) {
            $query = $query->where('ap.apply_time', '>=', $params['start_time']);
        }

        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $query = $query->where('ap.apply_time', '<=', $params['end_time']);
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $status = $params['status'];
            $query = $query->where('ap.status', "$status");
        }

        //充值信息类型（0：全部；1：历史有充值；2：昨日有充值；3：今日有充值）
        $rechargeType = $params['recharge_type'] ?? 0;

        $attributes['total'] = $query->count();
        $res = $query->orderBy('ap.created', 'desc')
            ->orderBy('ap.id', 'asc')
            ->forPage($page, $size)
            ->get()
            ->toArray();

        $user_ids = (array_unique(array_column($res, 'user_id')));
        $staticData = [];
        foreach ($user_ids as $value) {
            //当日统计
            $today_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->where('count_date', date('Y-m-d'))
                ->select(['deposit_user_amount', 'bet_user_amount'])
                ->first();
            $staticData[$value]['today_recharge_amount'] = $today_static ? $today_static->deposit_user_amount : 0;
            $staticData[$value]['today_bet_amount'] = $today_static ? $today_static->bet_user_amount : 0;

            //昨日充值金额
            $yesterday_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->where('count_date', date("Y-m-d",strtotime("-1 day")))
                ->value('deposit_user_amount');
            $staticData[$value]['yesterday_recharge_amount'] = $yesterday_static ?? 0;

            //总计统计
            $total_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->select(DB::raw('sum(deposit_user_amount) as deposit_user_amount,sum(bet_user_amount) as bet_user_amount'))
                ->first();
            $staticData[$value]['total_recharge_amount'] = $total_static ? $total_static->deposit_user_amount : 0;
            $staticData[$value]['total_bet_amount'] = $total_static ? $total_static->bet_user_amount : 0;

            //用户申请统计
            $success = DB::table('active_apply')
                         ->where('user_id', $value)
                         ->where('status', 'pass')
                         ->count();
            $sum = DB::table('active_apply')
                         ->where('user_id', $value)
                         ->count();
            $staticData[$value]['total_apply'] = intval($sum) ?? 0;
            $staticData[$value]['success_apply'] = intval($success) ?? 0;
        }

        foreach ($res as $key=>$v) {
            $v->today_recharge_amount = bcmul($staticData[$v->user_id]['today_recharge_amount'],100,0) ?? 0;
            $v->today_bet_amount = bcmul($staticData[$v->user_id]['today_bet_amount'],100,0);
            $v->total_recharge_amount = bcmul($staticData[$v->user_id]['total_recharge_amount'],100,0) ?? 0;
            $v->total_bet_amount = bcmul($staticData[$v->user_id]['total_bet_amount'],100,0);
            $v->total_apply = $staticData[$v->user_id]['total_apply'];
            $v->success_apply = $staticData[$v->user_id]['success_apply'];
            $v->money = $v->money ?? 0;    //返回结果单位为分
            $v->yesterday_recharge_amount = bcmul($staticData[$v->user_id]['yesterday_recharge_amount'],100,0) ?? 0;

            if($rechargeType == 1 && $v->total_recharge_amount == 0) {
                unset($res[$key]);
            }
            if($rechargeType == 2 && $v->yesterday_recharge_amount == 0) {
                unset($res[$key]);
            }
            if($rechargeType == 3 && $v->today_recharge_amount == 0) {
                unset($res[$key]);
            }
        }
        $res = array_values($res);

        if (!$res) {
            return [];
        }

        $attributes['number'] = $page;

        return $this->lang->set(0, [], $res, $attributes);
    }
};
