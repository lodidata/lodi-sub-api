<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';

    const TITLE = '转账记录';

    const DESCRIPTION = '';

    

    const QUERY = [
        'username'    => 'string() #用户名',
        'lower_limit' => 'int #转出金额开始范围',
        'upper_limit' => 'int #转出金额结束范围',
        'status'      => 'string # success,fail',
        'start_time'  => 'datetime #查询开始时间',
        'end_time'    => 'datetime #查询结束时间',
        "out_id"      => 'int #转出钱包id，参见接口：http://admin.las.me:8888/wallet/types?type=user&debug=1',
        'in_id'       => 'int #转入钱包id',
        'page_size'   => 'int',
        'page'        => 'int',
    ];

    

    const PARAMS = [];

    const SCHEMAS = [
        [
            "id"       => "int #记录id",
            "user_id"  => "int #用户id",
            "username" => "string #用户名",
            "out_id"   => "int #转出钱包id，参见接口 http://admin.las.me:8888/wallet/types?type=user&debug=1",
            "out_name" => "string #转出钱包名称，参见接口 http://admin.las.me:8888/wallet/types?type=user&debug=1",
            "in_id"    => "int #转入钱包id，参见接口 http://admin.las.me:8888/wallet/types?type=user&debug=1",
            "in_name"  => "string #转入钱包名称，参见接口 http://admin.las.me:8888/wallet/types?type=user&debug=1",
            "type"     => "int #类型：1主转子 2子转主",
            "amount"   => "int #转账金额",
            "no"       => "int #订单号",
            "status"   => "string　＃success,fail",
            "memo"     => "string　＃备注",
            "created"  => "string #创建日期",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $username = $this->request->getParam('username');
        $status = $this->request->getParam('status');
        $lower_limit = $this->request->getParam('lower_limit');
        $upper_limit = $this->request->getParam('upper_limit');
        $in_type = $this->request->getParam('in_type');
        $out_type = $this->request->getParam('out_type');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page') ?? 1;
        $size = $this->request->getParam('page_size') ?? 20;

        $query = \DB::connection('slave')->table('funds_transfer_log as tl')
                    ->leftJoin('admin_user AS admin', 'tl.opater_uid', '=', 'admin.id');

        $username && $query->where('tl.username', '=', $username);
        $status && $query->where('tl.status', '=', $status);
        $lower_limit && $query->where('tl.amount', '>=', $lower_limit);
        $upper_limit && $query->where('tl.amount', '<=', $upper_limit);
        $in_type && $query->where('tl.in_type', '=', $in_type);
        $out_type && $query->where('tl.out_type', '=', $out_type);
        $stime && $query->where('tl.created', '>=', $stime);
        $etime && $query->where('tl.created', '<=', $etime);

        $total = clone $query;

        $query = $query->orderBy('id', 'desc')
                       ->forPage($page, $size);

        $attributes['total'] = $total->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        $data = $query->get([
            'tl.id',
            'tl.user_id',
            'tl.username',
            'tl.out_id',
            'tl.out_name',
            'tl.in_id',
            'tl.in_name',
            'tl.type',
            'tl.amount',
            'tl.no',
            'tl.status',
            'tl.memo',
            'tl.created',
            'tl.username',
            'admin.username AS admin_user'
        ])
                      ->toArray();

        $data = array_map(function ($row) {
            $row->op_name = !empty($row->admin_user) ? $row->admin_user : $row->username;
            return $row;
        }, $data);

        return $this->lang->set(0, [], $data, $attributes);
    }
};
