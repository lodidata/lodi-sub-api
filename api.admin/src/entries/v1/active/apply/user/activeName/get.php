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
        $data = DB::table('active')
            ->select('name')
            ->where('state', 'apply')
            ->get()->toArray();

        return $this->lang->set(0, [], $data, []);
    }
};
