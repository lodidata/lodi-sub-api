<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '解除冻结ip';
    const DESCRIPTION = '';
    
    const QUERY = [];

    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = null) {
        $id = intval($id);

        if (empty($id)) {
            return $this->lang->set(-2);
        }

        $ip = \DB::table('ip_black')->where('id',$id)->value(\DB::raw('INET6_NTOA(ip) as ip'));

        if (empty($ip)) {
            return $this->lang->set(-2);
        }

        $key        = \Logic\Define\CacheKey::$perfix['blackIpLockedList']. $ip;
        $redis_key  = \Logic\Define\CacheKey::$perfix['blackIp']. $ip;
        $this->ci->redis->del($key);
        $this->ci->redis->del($redis_key);

        $result = \DB::table('ip_black')->where([
            'id' => $id
        ])->delete();

        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, '网络安全', '解除冻结ip', '删除', $result ? 1 : 0, 'ip：' . $ip);

        return [];
    }
};