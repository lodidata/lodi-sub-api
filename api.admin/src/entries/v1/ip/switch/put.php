<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\IpLimit;
return new class() extends BaseController {
    const TITLE = 'IP白名单状态';
    const DESCRIPTION = '获取IP白名单开关状态';

    const PARAMS = [
        'switch' => 'bool(required) 开关状态',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $status = $this->request->getParam('switch', false);
        if($status) {
            $ip_limit = new IpLimit();
            $data = $ip_limit->getList($condition = [], $page = 1, $page_size = 20);
            if (empty($data)) {
                return $this->lang->set(11010);
            }
        }
        $switch = \Logic\Set\SystemConfig::getModuleSystemConfig('login')['ip_limit_whitelist'] ?? false;
        if($switch['ip_limit_whitelist'] == $status) {
            return $this->lang->set(0);
        }
        \Model\Admin\SystemConfig::where('module', 'login')
            ->where('key', 'ip_limit_whitelist')->update(['value' => $status]);
        $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
        //写入操作日志
        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, '登录安全', '登录IP白名单开关', $status ? '开启' : '关闭', 1, "登录IP白名单开关[" . ($status ? '关闭' : '开启') . "]更改为[" . ($status ? '开启' : '关闭') . "]");

        return [];
    }
};





