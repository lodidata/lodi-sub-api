<?php

use Logic\Admin\BaseController;
use Model\IpLimit;
use Utils\Utils;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '修改允许登录IP';
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

    public function run($id = null) {
        $id = intval($id);

        if (empty($id)) {
            return $this->lang->set(-2);
        }

        $ip_limit = new IpLimit();

        $admin = $this->playLoad;

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

        $data = $ip_limit->where([
            'id' => $id,
        ])
                         ->first();


        if (empty($data)) {
            return $this->lang->set(-2);
        }

        $data['ip'] = Utils::RSADecrypt($data['ip']);

        $exist = $ip_limit->where('ip', $ip)
                          ->where('id', '!=', $id)
                          ->count();

        if ($exist > 0) {
            return $this->lang->set(10610);
        }

        $result = DB::table('ip_limit')->where('id', $id)
                           ->update([
                               'ip'         => $ip,
                               'mark'       => $mark,
                               'admin_id'   => $admin['uid'],
                               'admin_user' => $admin['nick'],
                           ]);

        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, 'ip白名单', 'ip白名单', '修改', $result ? 1 : 0, "ip：{$data['ip']}修改为: {$params['ip']}, 备注：{$data['mark']} 修改为 {$params['mark']}");

        return [];
    }
};