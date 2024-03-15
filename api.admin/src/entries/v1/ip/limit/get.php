<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Utils\Client;

return new class() extends BaseController {
    const TITLE = '获取允许登录IP列表';
    const DESCRIPTION = '列表中存在的IP才允许登录后台';

    const QUERY = [];

    const PARAMS = [
        'page'      => 'string() #当前页 默认为1',
        'page_size' => 'string() #每页数 默认为20',
        'ip'        => 'string() #IP',
    ];
    const SCHEMAS = [
            'list' => [
                [
                    'id'         => 'int #记录ID',
                    'ip'         => 'string #IP',
                    'admin_id'   => 'int #管理员ID',
                    'admin_user' => 'string #管理员用户名',
                    'mark'       => 'string #备注',
                ],
            ],
            'ip'   => 'string #当前IP',
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        $ip = $this->request->getParam('ip', false);

        $condition = [];
        if ($ip) {
            $condition['ip'] = Utils::RSAEncrypt($ip);
        }

        $ip_limit = new IpLimit();

        $data = $ip_limit->getList($condition, $page, $page_size) ?? [];
        $data = array_map(function ($row) {
            $row['ip'] = Utils::RSADecrypt($row['ip']);
            return $row;
        }, $data);

        $total = $ip_limit->total($condition);

        return $this->lang->set(0, [], [
            'list' => $data,
            'ip'   => Client::getIp(),
        ], [
            'total'  => $total,
            'number' => $page,
            'size'   => $page_size,
        ]);
    }
};
