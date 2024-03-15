<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Utils\Client;

return new class() extends BaseController {
    const TITLE = '冻结IP列表';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        'page'       => 'string() #当前页 默认为1',
        'page_size'  => 'string() #每页数 默认为20',
        'ip'         => 'string() #IP',
        'start_time' => 'string() #冻结开始时间',
        'end_time'   => 'string() #冻结结束时间',
        'account'    => 'string() #冻结账号',
    ];

    const SCHEMAS = [
        "ip"            => "string()冻结ip",
        "accounts_num"  => "int()冻结账号数",
        "operator"      => "string()操作者",
        "memo"          => "string()冻结原因",
        "created"       => "string()冻结时间",
        "valid_time"    => "string()解除冻结时间"
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $page       = $this->request->getParam('page', 1);
        $page_size  = $this->request->getParam('page_size', 20);
        $ip         = $this->request->getParam('ip');
        $account    = $this->request->getParam('account');
        $start_time = $this->request->getParam('start_time');
        $end_time   = $this->request->getParam('end_time');

        $query = \DB::table('ip_black')->selectRaw('id,INET6_NTOA(ip) as ip ,accounts_num,operator,memo,valid_time,created');

        $ip && $query->whereRaw("ip = INET6_ATON(?)",[$ip]);
        $start_time && $query->where('created','>=',$start_time);
        $end_time && $query->where('created','<=',$end_time);
        //已经解冻的 不显示
        $query->where('valid_time','>',date('Y-m-d H:i:s'));

        if($account){
            $login_ip = \DB::table('user')->where('name',$account)->value('login_ip');
            $login_ip && $query->where('ip',$login_ip);
        }

        $total_query = clone $query;
        $data =    $query->forPage($page, $page_size)->get();

        $total = $total_query->count()??0;
        return $this->lang->set(0, [], $data, [
            'total'  => $total,
            'page'   => $page,
            'size'   => $page_size,
        ]);
    }
};
