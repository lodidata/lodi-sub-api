<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '删除允许登录IP记录';
    const DESCRIPTION = '列表中存在的IP才允许登录后台';
    
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

        $ip_limit = new IpLimit();
        $data = $ip_limit->where([
            'id' => $id,
        ])->first();

        if (empty($data)) {
            return $this->lang->set(-2);
        }

        $result = $ip_limit->where([
            'id' => $id
        ])->delete();

        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, 'ip白名单', 'ip白名单', '删除', $result ? 1 : 0, 'ip：' . Utils::RSADecrypt($data['ip']));

        return [];
    }
};