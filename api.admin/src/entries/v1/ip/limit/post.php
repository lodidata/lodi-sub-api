<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '新增允许登录IP';
    const DESCRIPTION = '列表中存在的IP才允许登录后台';
    
    const QUERY = [];

    const PARAMS = [
        'ip'   => 'string(required) #ip地址',
        'mark' => 'string() #备注',
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

        $ip_limit = new IpLimit();

        $params = $this->request->getParams();

        $regExp = '/^((25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))$/';

        if (!preg_match($regExp, $params['ip'])) {
            return $this->lang->set(10612);
        }

        if (mb_strlen($params['mark']) > 100) {
            return $this->lang->set(10613);
        }

        $ip = Utils::RSAEncrypt($params['ip']);
        $mark = $params['mark'];

        $exist = $ip_limit->where('ip', $ip)
                          ->count();

        if ($exist > 0) {
            return $this->lang->set(10610);
        }

        $result = $ip_limit->insert([
            'ip'         => $ip,
            'mark'       => $mark,
            'admin_id'   => $playLoad['uid'],
            'admin_user' => $playLoad['nick'],
        ]);

        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, 'ip白名单', 'ip白名单', '新增', $result ? 1 : 0, "ip：{$params['ip']}, 备注：{$params['mark']}");

        return [];
    }
};