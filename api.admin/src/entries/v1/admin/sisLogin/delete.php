<?php

use Logic\Admin\BaseController;

return new class extends BaseController {
    const TITLE = "以token获取权限";
    const HINT = "";
    const DESCRIPTION = "";

    const PARAMS = [
        "name" => "string(required) #需要退出的用户名称",
    ];
    //前置方法
    protected $beforeActionList = [
    ];
    public function run() {
        $header = $this->request->getParam('token');
        $config = $this->ci->get('settings')['jsonwebtoken'];
        // 判断header是否携带token信息
        $token = substr($header, 7);
        $admin_token = new \Logic\Admin\AdminToken($this->ci);
        $data = $admin_token->decode($token, $config['public_key']);
        $uid = $admin_token->originId($data['uid'], $config['uid_digital']);
        // 从cookie、数据库中删除有效的token
        (new \Logic\Admin\Cache\AdminRedis($this->ci))->removeAdminUserCache($uid);
        //退出日志
        (new \Logic\Admin\Log($this->ci))->create($this->playLoad['uid'], $data['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $data['nick'].'退出系统',1,'手动退出');
        return $this->lang->set(0);
    }
};
