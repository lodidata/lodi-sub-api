<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '优惠申请列表';
    const DESCRIPTION = '获取会员参加的活动列表';
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
        //        $query = DB::table('active_apply as ap')
        $query = DB::connection('slave')->table('active_apply as ap')
            ->leftJoin('active as a', 'ap.active_id', '=', 'a.id')
            ->leftJoin('active_template as at', 'a.type_id', '=', 'at.id')
            ->leftJoin('user as u', 'u.id', '=', 'ap.user_id')
            ->select(DB::raw(
                'ap.id,a.type_id,ap.user_name,ap.user_id,
                    ap.active_id,a.title as active_name,
                    ap.deposit_money,ap.coupon_money,
                    ap.withdraw_require,ap.apply_time,
                    ap.updated,ap.content,ap.memo,at.name as template,
                    ap.status,ap.state,ap.updated as process_time'
            ))
            ->whereNotIn('u.tags', [4, 7])
            ->where('ap.status', '<>', 'undetermined')
            ->where('a.state', '<>', 'apply'); //用户申请的数据不显示在活动列表中

        if (isset($params['title']) && !empty($params['title'])) {
            $query = $query->where('title', 'LIKE', "%{$params['title']}%");
        }

        if (isset($params['user_name']) && !empty($params['user_name'])) {
            $user_name = $params['user_name'];
            $query = $query->where('ap.user_name', "$user_name");
        }

        if (isset($params['active_name']) && !empty($params['active_name'])) {
            $active_name = $params['active_name'];
            $query = $query->where('ap.active_name', "$active_name");
        }

        if (isset($params['type_id']) && !empty($params['type_id'])) {
            $query = $query->where('a.type_id', $params['type_id']);
        } else {
            $query = $query->where('a.type_id', '<>', 6);
        }

        if (isset($params['apply_time']) && !empty($params['apply_time'])) {
            $query = $query->where('apply_time', 'like', $params['apply_time'] . '%');
        }

        if (isset($params['start_time']) && !empty($params['start_time'])) {
            $query = $query->where('ap.created', '>=', $params['start_time']);
        }

        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $query = $query->where('ap.created', '<=', $params['end_time']);
        }

        if (isset($params['state']) && !empty($params['state'])) {
            $state = $params['state'];
            $query = $query->where('ap.state', "$state");
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $status = $params['status'];
            $query = $query->where('ap.status', "$status");
        }

        $attributes['total'] = $query->count();
        $res = $query->orderBy('ap.created', 'desc')
            ->orderBy('ap.id', 'asc')
            ->forPage($page, $size)
            ->get()
            ->toArray();

        if (!$res) {
            return [];
        }

        $attributes['number'] = $page;

        return $this->lang->set(0, [], $res, $attributes);
    }
};
