<?php

use Utils\Www\Action;
use Model\Active;

/**
 * 返回幸运转盘历史记录列表
 */
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取幸运轮盘历史记录列表";
    const TAGS = "优惠活动";
    const QUERY = [];
    const SCHEMAS = [
        [
            "coupon_money"      => "float() #优惠金额(手动申请的活动没有优惠金额) 如：1000",
            "apply_time"        => "string() #申请时间 如：2019-01-09 10:51:51",
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = (int)$this->auth->getUserId();

//        $userId=212244;//测试数据

        /* 查询幸运转盘配置*/
        $luckyData = DB::table('active')->select(['id'])->where('type_id', 6)->first();

        if (!$luckyData) {
            //不存在该类型活动
            return $this->lang->set(-2);
        }

        $data = DB::table('active_apply')->select(['coupon_money','apply_time'])
            ->where('active_id', $luckyData->id)
            ->where('user_id', $userId)
            ->limit(10)
            ->orderBy('id', 'desc')
            ->get()->toArray();

        return $data;
    }
};