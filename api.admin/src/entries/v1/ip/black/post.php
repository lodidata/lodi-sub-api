<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '新增冻结ip';
    const DESCRIPTION = '';
    
    const QUERY = [];

    const PARAMS = [
        'ip'        => 'string(required) #ip地址',
        'lock_time' => 'int() #冻结天数 0表示永久',
        'memo'      => 'string() #备注',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        global $playLoad;

        $params = $this->request->getParams();

        if (!filter_var($params['ip'],FILTER_VALIDATE_IP)) {
            return $this->lang->set(10612);
        }

        $ip         = $params['ip'];
        $mark       = $params['memo'];
        $lock_time  = $params['lock_time'];
        $lock_time == 0 && $lock_time = 365*2;
        $time       = 24*3600*$lock_time;
        $key        = \Logic\Define\CacheKey::$perfix['blackIpLockedList']. $ip;

        $res = $this->ci->redis->setex($key,$time,1);
        $data = [
            'ip'            => $ip,
            'valid_time'    => $lock_time,
            'operator'      => $playLoad['nick'],
            'memo'          => $mark,
        ];
        $result = \Utils\Client::insertBlackIp($data);

        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, '网络安全', '新增冻结ip', '新增', $result ? 1 : 0, "ip：{$ip}]}, 备注：{$mark}");

        return [];
    }
};